<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPanelAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->hasAnyRole(['Administrator', 'Sachbearbeiter'])) {
            // Platform operators have their own backoffice instead of a property management panel.
            return $user->isSuperAdmin() ? redirect('/superadmin') : redirect()->route('portal.dashboard');
        }

        return $next($request);
    }
}
