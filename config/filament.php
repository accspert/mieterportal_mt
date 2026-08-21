<?php

return [
    'brand' => [
        'name' => env('APP_NAME', 'Mieterportal'),
        'logo' => env('FILAMENT_LOGO', '/branding/logo.svg'),
        'favicon' => env('FILAMENT_FAVICON', '/branding/favicon.svg'),
    ],

    'colors' => [
        'primary' => env('FILAMENT_PRIMARY_COLOR', '#0ea5a4'), // teal-500
        'secondary' => env('FILAMENT_SECONDARY_COLOR', '#111827'), // gray-900
    ],

    // Default sidebar groups order (keeps in sync with provider)
    'navigation' => [
        'groups' => [
            'Objekte',
            'Mieter & Kontakte',
            'Dokumente',
            'Tickets',
            'Administration',
        ],
    ],
];
