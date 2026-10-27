<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Surface N+1 queries during development.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Stable morph names, so renaming a class never orphans ledger rows.
        Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'product' => \App\Models\Product::class,
            'sale' => \App\Models\Sale::class,
            'sale_item' => \App\Models\SaleItem::class,
            'sale_return' => \App\Models\SaleReturn::class,
            'purchase' => \App\Models\Purchase::class,
            'purchase_item' => \App\Models\PurchaseItem::class,
            'purchase_return' => \App\Models\PurchaseReturn::class,
            'stock_adjustment' => \App\Models\StockAdjustment::class,
            'payment' => \App\Models\Payment::class,
            'expense' => \App\Models\Expense::class,
            'customer' => \App\Models\Customer::class,
            'lead' => \App\Models\Lead::class,
            'lead_follow_up' => \App\Models\LeadFollowUp::class,
            'employee' => \App\Models\Employee::class,
            'staff_target' => \App\Models\StaffTarget::class,
            'incentive' => \App\Models\Incentive::class,
            'delivery' => \App\Models\Delivery::class,
            'supplier' => \App\Models\Supplier::class,
            'branch' => \App\Models\Branch::class,
            'category' => \App\Models\Category::class,
            'brand' => \App\Models\Brand::class,
            'unit' => \App\Models\Unit::class,
            'tax' => \App\Models\Tax::class,
            'product_variant' => \App\Models\ProductVariant::class,
            'setting' => \App\Models\Setting::class,
            'cash_register' => \App\Models\CashRegister::class,
            'expense_category' => \App\Models\ExpenseCategory::class,
            'role' => \Spatie\Permission\Models\Role::class,
        ]);

        Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);

        Paginator::useBootstrapFive();

        View::composer('layouts.*', function ($view) {
            $view->with('appSettings', app(SettingsService::class));
        });

        View::composer('layouts.header', function ($view) {
            $view->with('alerts', auth()->check() ? app(\App\Services\AlertService::class)->for(auth()->user()) : []);
        });
    }
}
