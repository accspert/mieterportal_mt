<?php

/*
|--------------------------------------------------------------------------
| Legal Information
|--------------------------------------------------------------------------
|
| Operator details shown on the imprint, privacy policy and terms pages.
|
*/

return [
    'company' => env('LEGAL_COMPANY', 'Accspert Software GmbH i.Gr.'),
    'managing_director' => env('LEGAL_MANAGING_DIRECTOR', 'Eren Gürkaynar'),
    'street' => env('LEGAL_STREET', 'Breisacherstrasse 83'),
    'postal_code' => env('LEGAL_POSTAL_CODE', '4057'),
    'city' => env('LEGAL_CITY', 'Basel'),
    'country' => env('LEGAL_COUNTRY', 'Schweiz'),
    'email' => env('LEGAL_EMAIL', 'info@liegenschaftsportal.ch'),
    'phone' => env('LEGAL_PHONE'),

    /**
     * Company identification number (UID, CHE-xxx.xxx.xxx). Available after entry in the commercial register.
     */
    'uid' => env('LEGAL_UID'),
    'commercial_register' => env('LEGAL_COMMERCIAL_REGISTER', 'Handelsregister des Kantons Basel-Stadt'),

    /**
     * Hosting provider and server location, named in the privacy policy.
     */
    'hosting_provider' => env('LEGAL_HOSTING_PROVIDER'),
    'hosting_location' => env('LEGAL_HOSTING_LOCATION', 'Schweiz'),

    'jurisdiction' => env('LEGAL_JURISDICTION', 'Basel'),
    'last_updated' => env('LEGAL_LAST_UPDATED', '1. Oktober 2026'),
];
