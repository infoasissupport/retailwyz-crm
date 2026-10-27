<?php

/*
| Sidebar navigation. Each item is shown only when the user holds `can`
| (a permission name); groups are hidden when no child is visible.
| Hiding a link is cosmetic: every route enforces its own authorization.
| `icon` is a Bootstrap Icons class (https://icons.getbootstrap.com).
*/

return [
    ['label' => 'Dashboard', 'icon' => 'bi-house-door', 'route' => 'dashboard', 'can' => 'dashboard.view', 'active' => 'dashboard'],

    ['label' => 'Sales', 'icon' => 'bi-bag-check', 'children' => [
        ['label' => 'Leads', 'icon' => 'bi-person-lines-fill', 'route' => 'leads.index', 'can' => 'leads.view', 'active' => ['leads.index', 'leads.create', 'leads.show', 'leads.edit']],
        ['label' => 'Follow-ups', 'icon' => 'bi-calendar-check', 'route' => 'leads.follow-ups.index', 'can' => 'leads.view', 'active' => 'leads.follow-ups.*'],
        ['label' => 'New sale', 'icon' => 'bi-plus-circle', 'route' => 'sales.create', 'can' => 'sales.create', 'active' => 'sales.create'],
        ['label' => 'Orders', 'icon' => 'bi-receipt', 'route' => 'sales.index', 'can' => 'sales.view', 'active' => ['sales.index', 'sales.show', 'sales.invoice']],
        ['label' => 'Returns', 'icon' => 'bi-arrow-counterclockwise', 'route' => 'sales.returns.index', 'can' => 'sales.view', 'active' => 'sales.returns.*'],
        ['label' => 'Customers', 'icon' => 'bi-people', 'route' => 'customers.index', 'can' => 'customers.view', 'active' => 'customers.*'],
    ]],

    ['label' => 'Products', 'icon' => 'bi-box-seam', 'children' => [
        ['label' => 'All products', 'icon' => 'bi-grid', 'route' => 'products.index', 'can' => 'products.view', 'active' => 'products.*'],
        ['label' => 'Categories', 'icon' => 'bi-tags', 'route' => 'categories.index', 'can' => 'catalog.manage', 'active' => 'categories.*'],
        ['label' => 'Brands', 'icon' => 'bi-award', 'route' => 'brands.index', 'can' => 'catalog.manage', 'active' => 'brands.*'],
        ['label' => 'Units', 'icon' => 'bi-rulers', 'route' => 'units.index', 'can' => 'catalog.manage', 'active' => 'units.*'],
    ]],

    ['label' => 'Inventory', 'icon' => 'bi-boxes', 'children' => [
        ['label' => 'Stock items', 'icon' => 'bi-clipboard-data', 'route' => 'inventory.index', 'can' => 'inventory.view', 'active' => 'inventory.index'],
        ['label' => 'Purchase', 'icon' => 'bi-cart3', 'route' => 'purchases.index', 'can' => 'purchases.view', 'active' => 'purchases.*'],
        ['label' => 'Suppliers', 'icon' => 'bi-truck-flatbed', 'route' => 'suppliers.index', 'can' => 'suppliers.view', 'active' => 'suppliers.*'],
        ['label' => 'Stock in', 'icon' => 'bi-box-arrow-in-down', 'route' => 'inventory.stock-in', 'can' => 'inventory.adjust', 'active' => 'inventory.stock-in'],
        ['label' => 'Stock out', 'icon' => 'bi-box-arrow-up', 'route' => 'inventory.stock-out', 'can' => 'inventory.adjust', 'active' => 'inventory.stock-out'],
        ['label' => 'Stock adjustment', 'icon' => 'bi-sliders', 'route' => 'inventory.adjustments.index', 'can' => 'inventory.view', 'active' => 'inventory.adjustments.*'],
        ['label' => 'Movements', 'icon' => 'bi-arrow-left-right', 'route' => 'inventory.movements', 'can' => 'inventory.view', 'active' => 'inventory.movements'],
    ]],

    ['label' => 'Operations', 'icon' => 'bi-gear-wide-connected', 'children' => [
        ['label' => 'Delivery', 'icon' => 'bi-truck', 'route' => 'deliveries.index', 'can' => 'deliveries.view', 'active' => 'deliveries.*'],
    ]],

    ['label' => 'Staff', 'icon' => 'bi-person-badge', 'children' => [
        ['label' => 'Employees', 'icon' => 'bi-person-vcard', 'route' => 'staff.employees.index', 'can' => 'staff.view', 'active' => 'staff.employees.*'],
        ['label' => 'Attendance', 'icon' => 'bi-calendar3', 'route' => 'staff.attendance.index', 'can' => 'staff.view', 'active' => 'staff.attendance.*'],
        ['label' => 'Targets', 'icon' => 'bi-bullseye', 'route' => 'staff.targets.index', 'can' => 'staff.view', 'active' => 'staff.targets.*'],
        ['label' => 'Incentives', 'icon' => 'bi-trophy', 'route' => 'staff.incentives.index', 'can' => 'staff.view', 'active' => 'staff.incentives.*'],
    ]],

    ['label' => 'Finance', 'icon' => 'bi-bank', 'children' => [
        ['label' => 'Overview', 'icon' => 'bi-pie-chart', 'route' => 'finance.index', 'can' => 'finance.view', 'active' => ['finance.index', 'finance.transactions']],
        ['label' => 'Payments', 'icon' => 'bi-credit-card', 'route' => 'payments.index', 'can' => 'payments.view', 'active' => 'payments.index'],
        ['label' => 'Receivables', 'icon' => 'bi-arrow-down-left-circle', 'route' => 'payments.receivables', 'can' => 'payments.view', 'active' => 'payments.receivables'],
        ['label' => 'Payables', 'icon' => 'bi-arrow-up-right-circle', 'route' => 'payments.payables', 'can' => 'payments.view', 'active' => 'payments.payables'],
        ['label' => 'Expenses', 'icon' => 'bi-wallet2', 'route' => 'expenses.index', 'can' => 'expenses.view', 'active' => 'expenses.*'],
        ['label' => 'Cash register', 'icon' => 'bi-cash-stack', 'route' => 'finance.cash-register', 'can' => 'cash-register.manage', 'active' => 'finance.cash-register*'],
    ]],

    ['label' => 'Reports', 'icon' => 'bi-bar-chart-line', 'route' => 'reports.index', 'can' => 'reports.view', 'active' => 'reports.*'],

    ['label' => 'Settings', 'icon' => 'bi-gear', 'children' => [
        ['label' => 'General', 'icon' => 'bi-sliders2', 'route' => 'settings.general', 'can' => 'settings.manage', 'active' => ['settings.general', 'settings.invoice']],
        ['label' => 'Users', 'icon' => 'bi-person-gear', 'route' => 'users.index', 'can' => 'users.view', 'active' => 'users.*'],
        ['label' => 'Roles & permissions', 'icon' => 'bi-shield-lock', 'route' => 'roles.index', 'can' => 'roles.manage', 'active' => 'roles.*'],
        ['label' => 'Branches', 'icon' => 'bi-shop', 'route' => 'settings.branches', 'can' => 'branches.manage', 'active' => 'settings.branches*'],
        ['label' => 'Taxes', 'icon' => 'bi-percent', 'route' => 'settings.taxes', 'can' => 'settings.manage', 'active' => 'settings.taxes*'],
    ]],
];
