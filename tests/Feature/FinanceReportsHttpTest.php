<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\CashRegister;
use App\Models\ExpenseCategory;
use App\Models\FinancialTransaction;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\ReportService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinanceReportsHttpTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->admin = $this->userWithRole();
    }

    /** Sale of 2 units, cost 100, price 200, 18% tax exclusive => 472.00 total. */
    private function makeSale(?User $user = null, array $payments = []): Sale
    {
        $product = $this->productWithStock('10');

        return app(SaleService::class)->create([
            'branch_id' => $this->branch->id,
            'sale_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => '2']],
            'payments' => $payments,
        ], $user ?? $this->admin);
    }

    public function test_super_admin_can_open_every_page_in_scope(): void
    {
        $sale = $this->makeSale();
        app(PurchaseService::class)->create([
            'branch_id' => $this->branch->id,
            'supplier_id' => Supplier::factory()->create()->id,
            'purchase_date' => now()->toDateString(),
            'receive_now' => true,
            'items' => [['product_id' => $sale->items()->value('product_id'), 'quantity' => '5', 'unit_cost' => '90']],
        ], $this->admin);
        app(\App\Services\PaymentService::class)->record($sale, ['amount' => '100', 'method' => 'cash'], $this->admin);

        $routes = [
            'dashboard', 'payments.index', 'payments.receivables', 'payments.payables',
            'expenses.index', 'expenses.create', 'expenses.categories.index',
            'finance.index', 'finance.transactions', 'finance.cash-register',
            'reports.index', 'reports.sales', 'reports.purchases', 'reports.stock', 'reports.profit-loss', 'reports.tax',
        ];

        foreach ($routes as $route) {
            $this->actingAs($this->admin)->get(route($route))->assertOk();
        }
    }

    public function test_recording_a_sale_payment_over_http_updates_payment_status(): void
    {
        $sale = $this->makeSale();

        $this->actingAs($this->admin)
            ->post(route('sales.payments.store', $sale), ['amount' => '472.00', 'method' => 'upi', 'paid_on' => now()->toDateString()])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(PaymentStatus::Paid, $sale->refresh()->payment_status);
    }

    public function test_refund_requires_refund_permission(): void
    {
        $cashier = $this->userWithRole('Cashier');
        $sale = $this->makeSale(payments: [['amount' => '472', 'method' => 'cash']]);

        $this->actingAs($cashier)
            ->post(route('sales.payments.store', $sale), ['amount' => '10', 'method' => 'cash', 'paid_on' => now()->toDateString(), 'refund' => 1])
            ->assertForbidden();

        $this->assertSame('472.00', $sale->refresh()->paid_amount);
    }

    public function test_expense_create_posts_ledger_row_and_delete_removes_it(): void
    {
        Storage::fake('local');
        $category = ExpenseCategory::factory()->create();

        $this->actingAs($this->admin)->post(route('expenses.store'), [
            'expense_category_id' => $category->id,
            'expense_date' => now()->toDateString(),
            'amount' => '1180.00',
            'tax_amount' => '180.00',
            'payment_method' => 'bank_transfer',
            'attachment' => UploadedFile::fake()->create('bill.pdf', 10, 'application/pdf'),
        ])->assertRedirect(route('expenses.index'));

        $expense = \App\Models\Expense::firstOrFail();
        Storage::disk('local')->assertExists($expense->attachment_path);
        $this->assertDatabaseHas('financial_transactions', [
            'source_type' => 'expense', 'source_id' => $expense->id, 'direction' => 'out', 'account' => 'bank',
        ]);

        $this->actingAs($this->admin)->delete(route('expenses.destroy', $expense))->assertRedirect();

        $this->assertSame(0, FinancialTransaction::where('source_type', 'expense')->count());
        Storage::disk('local')->assertMissing($expense->attachment_path);
    }

    public function test_cash_register_expected_cash_includes_cash_sale_payment(): void
    {
        $this->actingAs($this->admin)
            ->post(route('finance.cash-register.open'), ['opening_balance' => '500.00'])
            ->assertRedirect(route('finance.cash-register'));

        $this->makeSale(payments: [['amount' => '472', 'method' => 'cash']]);
        $register = CashRegister::firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('finance.cash-register.close', $register), ['closing_balance' => '970.00'])
            ->assertRedirect();

        $register->refresh();
        $this->assertSame(CashRegister::CLOSED, $register->status);
        $this->assertSame('972.00', $register->expected_balance);
        $this->assertSame('970.00', $register->closing_balance);
    }

    public function test_profit_and_loss_for_a_known_sale(): void
    {
        $this->makeSale();

        $report = app(ReportService::class)->profitAndLoss(
            now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString().' 23:59:59', $this->branch->id,
        );

        $this->assertSame('400.00', $report['revenue']);
        $this->assertSame('200.00', $report['cogs']);
        $this->assertSame('200.00', $report['gross_profit']);

        $tax = app(ReportService::class)->tax(
            now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString().' 23:59:59', $this->branch->id,
        );
        $this->assertSame('72.00', $tax['output_total']);
    }

    public function test_cashier_sees_limited_dashboard_and_cannot_open_reports(): void
    {
        $cashier = $this->userWithRole('Cashier');
        $this->makeSale();

        $this->actingAs($cashier)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total sales')
            ->assertSee('Recent Leads')
            ->assertDontSee('Low stock items') // Cashier lacks inventory.view
            ->assertDontSee('Low Stock Alerts');

        $this->actingAs($cashier)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('finance.index'))->assertForbidden();
    }

    public function test_user_without_dashboard_permission_still_lands_on_dashboard(): void
    {
        $user = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Use the menu');
    }
}
