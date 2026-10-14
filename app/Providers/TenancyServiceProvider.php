<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Sentry\State\Scope;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Listeners;

use function Sentry\configureScope;

/**
 * Single-database tenancy: all property managements share one database and one domain.
 * The tenant is activated from the logged-in user (InitializeTenancyFromUser); the
 * bootstrappers only separate the file storage and carry the tenant into queued jobs.
 */
class TenancyServiceProvider extends ServiceProvider
{
    // By default, no namespace is used to support the callable array syntax.
    public static string $controllerNamespace = '';

    public function events(): array
    {
        return [
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class,
            ],
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],
            Events\TenancyBootstrapped::class => [
                function (Events\TenancyBootstrapped $event) {
                    $tenantKey = (string) $event->tenancy->tenant->getTenantKey();

                    configureScope(fn (Scope $scope) => $scope->setTag('tenant', $tenantKey));
                },
            ],
            Events\RevertedToCentralContext::class => [
                function () {
                    configureScope(fn (Scope $scope) => $scope->removeTag('tenant'));
                },
            ],
        ];
    }

    public function boot(): void
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }

        $this->app->booted(function () {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)
                    ->group(base_path('routes/tenant.php'));
            }
        });
    }
}
