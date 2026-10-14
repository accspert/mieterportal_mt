<?php

namespace App\Http\Middleware;

use App\Models\CompanyTenant;
use App\Services\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks every request to a deactivated tenant (e.g. cancelled or unpaid subscription).
 *
 * Must run after the tenancy initialization middleware.
 */
class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        // A super admin in support mode may look into a blocked property management.
        if ($tenant instanceof CompanyTenant && ! $tenant->isActive() && ! ImpersonationService::isImpersonating()) {
            abort(403, __('Dieser Mandantenzugang ist gesperrt. Bitte wenden Sie sich an Ihre Verwaltung.'));
        }

        return $next($request);
    }
}
