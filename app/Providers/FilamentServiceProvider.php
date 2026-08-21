<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Basic branding and navigation groups registration
        if (class_exists(Filament::class)) {
            Filament::serving(function () {
                Filament::registerNavigationGroups([
                    'Objekte',
                    'Mieter & Kontakte',
                    'Dokumente',
                    'Tickets',
                    'Administration',
                ]);

                // Render the brand partial into Filament's brand slot if present
                Filament::registerRenderHook('brand', function () {
                    return view('filament.brand')->render();
                });

                Filament::registerRenderHook('global-search.results', function () {
                    return null;
                });
            });
        }
    }
}
