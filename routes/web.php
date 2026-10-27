<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadFollowUpController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Settings\BranchController;
use App\Http\Controllers\Settings\SettingController;
use App\Http\Controllers\Settings\TaxController;
use App\Http\Controllers\Staff\AttendanceController;
use App\Http\Controllers\Staff\EmployeeController;
use App\Http\Controllers\Staff\IncentiveController;
use App\Http\Controllers\Staff\StaffTargetController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
| Permission checks live in each controller's middleware() method and in
| policies; the groups here only handle authentication.
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('search', [SearchController::class, 'index'])->name('search');

    // Catalogue
    Route::get('products/lookup', [ProductController::class, 'lookup'])->name('products.lookup');
    Route::resource('products', ProductController::class);
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('brands', BrandController::class)->except('show');
    Route::resource('units', UnitController::class)->except('show');

    // Inventory
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('movements', [InventoryController::class, 'movements'])->name('movements');
        Route::get('stock-in', [StockAdjustmentController::class, 'stockIn'])->name('stock-in');
        Route::get('stock-out', [StockAdjustmentController::class, 'stockOut'])->name('stock-out');
        Route::get('adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
        Route::post('adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
        Route::post('adjustments/{adjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('adjustments.approve');
        Route::post('adjustments/{adjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('adjustments.reject');
    });

    // Purchases
    Route::resource('purchases', PurchaseController::class)->except('destroy');
    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
    Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::post('purchases/{purchase}/returns', [PurchaseReturnController::class, 'store'])->name('purchases.returns.store');
    Route::post('purchases/{purchase}/payments', [PaymentController::class, 'storeForPurchase'])->name('purchases.payments.store');

    // Sales
    Route::get('sales/returns', [SaleReturnController::class, 'index'])->name('sales.returns.index');
    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('sales/{sale}/invoice', [SaleController::class, 'invoice'])->name('sales.invoice');
    Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    Route::post('sales/{sale}/returns', [SaleReturnController::class, 'store'])->name('sales.returns.store');
    Route::post('sales/{sale}/payments', [PaymentController::class, 'storeForSale'])->name('sales.payments.store');

    // Leads
    Route::get('leads/follow-ups', [LeadFollowUpController::class, 'index'])->name('leads.follow-ups.index');
    Route::post('leads/follow-ups/{followUp}/complete', [LeadFollowUpController::class, 'complete'])->name('leads.follow-ups.complete');
    Route::post('leads/follow-ups/{followUp}/cancel', [LeadFollowUpController::class, 'cancel'])->name('leads.follow-ups.cancel');
    Route::resource('leads', LeadController::class);
    Route::post('leads/{lead}/follow-ups', [LeadFollowUpController::class, 'store'])->name('leads.follow-ups.store');
    Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');

    // Staff
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::resource('employees', EmployeeController::class);

        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('attendance/register', [AttendanceController::class, 'register'])->name('attendance.register');

        Route::get('targets', [StaffTargetController::class, 'index'])->name('targets.index');
        Route::post('targets', [StaffTargetController::class, 'store'])->name('targets.store');
        Route::post('targets/generate-incentives', [StaffTargetController::class, 'generateIncentives'])->name('targets.generate-incentives');
        Route::put('targets/{target}', [StaffTargetController::class, 'update'])->name('targets.update');
        Route::delete('targets/{target}', [StaffTargetController::class, 'destroy'])->name('targets.destroy');

        Route::get('incentives', [IncentiveController::class, 'index'])->name('incentives.index');
        Route::post('incentives', [IncentiveController::class, 'store'])->name('incentives.store');
        Route::post('incentives/{incentive}/approve', [IncentiveController::class, 'approve'])->name('incentives.approve');
        Route::post('incentives/{incentive}/reject', [IncentiveController::class, 'reject'])->name('incentives.reject');
        Route::post('incentives/{incentive}/pay', [IncentiveController::class, 'pay'])->name('incentives.pay');
    });

    // Deliveries
    Route::resource('deliveries', DeliveryController::class)->except('destroy');
    Route::post('deliveries/{delivery}/start', [DeliveryController::class, 'start'])->name('deliveries.start');
    Route::post('deliveries/{delivery}/complete', [DeliveryController::class, 'complete'])->name('deliveries.complete');
    Route::post('deliveries/{delivery}/fail', [DeliveryController::class, 'fail'])->name('deliveries.fail');
    Route::post('deliveries/{delivery}/cancel', [DeliveryController::class, 'cancel'])->name('deliveries.cancel');

    // Parties
    Route::resource('customers', CustomerController::class);
    Route::resource('suppliers', SupplierController::class);

    // Payments
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('receivables', [PaymentController::class, 'receivables'])->name('receivables');
        Route::get('payables', [PaymentController::class, 'payables'])->name('payables');
    });

    // Expenses
    Route::get('expenses/categories', [ExpenseCategoryController::class, 'index'])->name('expenses.categories.index');
    Route::post('expenses/categories', [ExpenseCategoryController::class, 'store'])->name('expenses.categories.store');
    Route::put('expenses/categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('expenses.categories.update');
    Route::delete('expenses/categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('expenses.categories.destroy');
    Route::resource('expenses', ExpenseController::class)->except('show');

    // Finance
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
        Route::get('transactions', [FinanceController::class, 'transactions'])->name('transactions');
        Route::get('cash-register', [CashRegisterController::class, 'index'])->name('cash-register');
        Route::post('cash-register', [CashRegisterController::class, 'open'])->name('cash-register.open');
        Route::post('cash-register/{cashRegister}/close', [CashRegisterController::class, 'close'])->name('cash-register.close');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->controller(ReportController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('sales', 'sales')->name('sales');
        Route::get('purchases', 'purchases')->name('purchases');
        Route::get('stock', 'stock')->name('stock');
        Route::get('profit-loss', 'profitLoss')->name('profit-loss');
        Route::get('tax', 'tax')->name('tax');
    });

    // Administration
    Route::resource('users', UserController::class);
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::get('roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
    Route::put('roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('general', [SettingController::class, 'general'])->name('general');
        Route::put('general', [SettingController::class, 'updateGeneral'])->name('general.update');
        Route::get('invoice', [SettingController::class, 'invoice'])->name('invoice');
        Route::put('invoice', [SettingController::class, 'updateInvoice'])->name('invoice.update');

        Route::get('branches', [BranchController::class, 'index'])->name('branches');
        Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
        Route::put('branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');

        Route::get('taxes', [TaxController::class, 'index'])->name('taxes');
        Route::post('taxes', [TaxController::class, 'store'])->name('taxes.store');
        Route::put('taxes/{tax}', [TaxController::class, 'update'])->name('taxes.update');
        Route::delete('taxes/{tax}', [TaxController::class, 'destroy'])->name('taxes.destroy');
    });
});
