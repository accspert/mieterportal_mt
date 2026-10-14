<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The /superadmin backoffice is reserved for the platform operator.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isSuperAdmin()) {
            abort(403, __('Zugriff verweigert. Dieser Bereich ist ausschliesslich für den Plattform-Betrieb reserviert.'));
        }

        return $next($request);
    }
}
