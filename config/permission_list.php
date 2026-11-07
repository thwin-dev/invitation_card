<?php

/*
| Permission modules use stable keys referenced by routes and authorization checks.
| Set an action to true only when that action is available in the current Blade UI.
*/

return [
    'exhibitors' => [
        'name' => 'Exhibitors',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'import' => true,
            'export' => false,
        ],
    ],
    'customers' => [
        'name' => 'Exhibitors',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'import' => true,
            'export' => false,
        ],
    ],
    'events' => [
        'name' => 'Event Details',
        'permissions' => [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => false,
            'import' => false,
            'export' => false,
        ],
    ],
];
