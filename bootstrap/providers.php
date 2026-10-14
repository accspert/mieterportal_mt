<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\SuperadminPanelProvider;
use App\Providers\FilamentServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    EventServiceProvider::class,
    FilamentServiceProvider::class,
    AdminPanelProvider::class,
    SuperadminPanelProvider::class,
    TenancyServiceProvider::class,
];
