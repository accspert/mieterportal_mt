<?php

namespace App\Http\Middleware;

use App\Models\CompanyTenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Activates the property management (tenant) of the logged-in user. There are no tenant
 * subdomains: the user account itself determines which data the request may see.
 *
 * Registered in the middleware priority list before SubstituteBindings, so route model
 * binding already resolves records within the user's property management.
 */
class InitializeTenancyFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && filled($user->company_tenant_id)) {
            $isAlreadyActive = tenancy()->initialized && tenant()->getTenantKey() === $user->company_tenant_id;

            if (! $isAlreadyActive) {
                tenancy()->initialize(CompanyTenant::query()->findOrFail($user->company_tenant_id));
            }
        }

        return $next($request);
    }
}
