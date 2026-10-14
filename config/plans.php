<?php

/*
|--------------------------------------------------------------------------
| Subscription Plans
|--------------------------------------------------------------------------
|
| Plans offered on the registration page. The key is stored on the tenant
| (company_tenants.plan). Add further plans here; set 'available' to false
| to show a plan without allowing sign-ups for it yet.
|
*/

return [
    'default' => 'standard',

    'plans' => [
        'standard' => [
            'name' => 'Standard',
            'price' => 'Kostenlos',
            'description' => 'Alle Funktionen des Liegenschaftsportals für Ihre Verwaltung.',
            'features' => [
                'Schadenmeldungen mit Fotos und Status',
                'Dokumente, Rechnungen und Aushänge',
                'Nachrichten zwischen Mieterschaft und Verwaltung',
                'Eigene Datenbank für Ihre Verwaltung',
                'Zwei-Faktor-Anmeldung und tägliche Backups',
            ],
            'available' => true,
        ],
    ],
];
