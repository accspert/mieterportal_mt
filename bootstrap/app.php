<?php

use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\InitializeTenancyFromUser;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            InitializeTenancyFromUser::class,
            EnsureTenantIsActive::class,
        ]);

        // The property management of the user must be active before route model binding,
        // otherwise records from URLs would be resolved without the tenant scope.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: InitializeTenancyFromUser::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
    })->create();
