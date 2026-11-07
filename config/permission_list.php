<?php

/*
| Permission modules use stable keys referenced by routes and authorization checks.
| Set an action to true only when that action is available in the current Blade UI.
*/

return [
    'dashboard' => [
        'name' => 'Dashboard',
        'permissions' => [
            'view' => true,
            'create' => false,
            'edit' => false,
            'delete' => false,
            'import' => false,
            'export' => false,
        ],
    ],
    'users' => [
        'name' => 'Users',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => false,
            'import' => false,
            'export' => false,
        ],
    ],
    'roles' => [
        'name' => 'Roles and permissions',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'import' => false,
            'export' => false,
        ],
    ],
    'activity-logs' => [
        'name' => 'Activity and audit logs',
        'permissions' => [
            'view' => true,
            'create' => false,
            'edit' => false,
            'delete' => false,
            'import' => false,
            'export' => true,
        ],
    ],
    'backups' => [
        'name' => 'Automatic backups',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => false,
            'delete' => true,
            'import' => false,
            'export' => true,
            'restore' => true,
        ],
    ],
    'products' => [
        'name' => 'Products and brands',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'import' => false,
            'export' => false,
        ],
    ],
    'categories' => [
        'name' => 'Product categories',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'import' => false,
            'export' => false,
        ],
    ],
    'subcategories' => [
        'name' => 'Product subcategories',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'import' => false,
            'export' => false,
        ],
    ],
    'units' => [
        'name' => 'Product units',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'import' => false,
            'export' => false,
        ],
    ],
    'unit-relations' => [
        'name' => 'Product unit relations',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'import' => false,
            'export' => false,
        ],
    ],
    'discounts' => [
        'name' => 'Branch discount rules',
        'permissions' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false, 'import' => false, 'export' => false],
    ],
    'sales' => [
        'name' => 'Sales',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'import' => false,
            'export' => true,
        ],
    ],
    'orders' => [
        'name' => 'Orders',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'import' => false,
            'export' => true,
        ],
    ],
    'customers' => [
        'name' => 'Customers',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'import' => false,
            'export' => false,
        ],
    ],
    'purchases' => [
        'name' => 'Purchases',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'import' => false,
            'export' => true,
        ],
    ],
    'suppliers' => [
        'name' => 'Suppliers',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'import' => false,
            'export' => false,
        ],
    ],
    'inventory' => [
        'name' => 'Inventory and stock',
        'permissions' => [
            'view' => true,
            'create' => false,
            'edit' => true,
            'delete' => false,
            'import' => false,
            'export' => false,
        ],
    ],
    'branches' => [
        'name' => 'Shops and branch prices',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => false,
            'import' => false,
            'export' => false,
        ],
    ],
    'expenses' => [
        'name' => 'Expenses',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => false,
            'delete' => false,
            'import' => false,
            'export' => false,
        ],
    ],
    'reports' => [
        'name' => 'Reports',
        'permissions' => [
            'view' => true,
            'create' => false,
            'edit' => false,
            'delete' => false,
            'import' => false,
            'export' => false,
        ],
    ],
];
