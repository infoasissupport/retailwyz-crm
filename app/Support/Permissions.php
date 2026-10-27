<?php

namespace App\Support;

use App\Models\User;

/**
 * Single registry of permission names and default role grants. The seeder
 * syncs these into spatie/laravel-permission; routes, policies and the
 * sidebar reference the same names.
 */
final class Permissions
{
    /** @var array<string, list<string>> module => permissions */
    public const MODULES = [
        'Dashboard' => ['dashboard.view'],
        'Products' => ['products.view', 'products.create', 'products.update', 'products.delete', 'products.view-cost', 'catalog.manage'],
        'Inventory' => ['inventory.view', 'inventory.adjust', 'inventory.approve'],
        'Purchases' => ['purchases.view', 'purchases.create', 'purchases.update', 'purchases.receive', 'purchases.cancel', 'purchases.return'],
        'Sales' => ['sales.view', 'sales.create', 'sales.cancel', 'sales.return', 'sales.override-price'],
        'Customers' => ['customers.view', 'customers.manage'],
        'Leads' => ['leads.view', 'leads.manage'],
        'Deliveries' => ['deliveries.view', 'deliveries.manage'],
        'Staff' => ['staff.view', 'staff.manage', 'attendance.manage', 'targets.manage', 'incentives.manage', 'incentives.approve'],
        'Suppliers' => ['suppliers.view', 'suppliers.manage'],
        'Payments' => ['payments.view', 'payments.create', 'payments.refund'],
        'Expenses' => ['expenses.view', 'expenses.manage'],
        'Finance' => ['finance.view', 'cash-register.manage'],
        'Reports' => ['reports.view', 'reports.financial'],
        'Administration' => ['users.view', 'users.manage', 'roles.manage', 'settings.manage', 'branches.manage', 'branches.access-all', 'audit.view'],
    ];

    /** Roles created by the seeder. Super Admin is granted everything via Gate::before. */
    public const ROLES = [
        User::SUPER_ADMIN_ROLE => [],
        'Branch Manager' => [
            'dashboard.view',
            'products.view', 'products.create', 'products.update', 'products.view-cost', 'catalog.manage',
            'inventory.view', 'inventory.adjust', 'inventory.approve',
            'purchases.view', 'purchases.create', 'purchases.update', 'purchases.receive', 'purchases.cancel', 'purchases.return',
            'sales.view', 'sales.create', 'sales.cancel', 'sales.return', 'sales.override-price',
            'customers.view', 'customers.manage', 'suppliers.view', 'suppliers.manage',
            'leads.view', 'leads.manage', 'deliveries.view', 'deliveries.manage',
            'staff.view', 'staff.manage', 'attendance.manage', 'targets.manage', 'incentives.manage',
            'payments.view', 'payments.create', 'payments.refund',
            'expenses.view', 'expenses.manage', 'finance.view', 'cash-register.manage',
            'reports.view', 'reports.financial', 'users.view',
        ],
        'Cashier' => [
            'dashboard.view', 'products.view',
            'sales.view', 'sales.create',
            'customers.view', 'customers.manage', 'leads.view', 'leads.manage',
            'deliveries.view', 'deliveries.manage',
            'payments.view', 'payments.create', 'cash-register.manage',
        ],
        'Storekeeper' => [
            'dashboard.view', 'products.view', 'products.create', 'products.update', 'catalog.manage',
            'inventory.view', 'inventory.adjust',
            'purchases.view', 'purchases.receive', 'suppliers.view',
            'deliveries.view', 'deliveries.manage',
        ],
        'Accountant' => [
            'dashboard.view', 'products.view', 'products.view-cost',
            'sales.view', 'purchases.view', 'customers.view', 'suppliers.view',
            'payments.view', 'payments.create', 'payments.refund',
            'expenses.view', 'expenses.manage', 'finance.view',
            'reports.view', 'reports.financial',
            'staff.view', 'incentives.approve', 'deliveries.view',
        ],
    ];

    /** @return list<string> */
    public static function all(): array
    {
        return array_merge(...array_values(self::MODULES));
    }
}
