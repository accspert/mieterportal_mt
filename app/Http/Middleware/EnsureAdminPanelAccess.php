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
            return redirect()->route('portal.dashboard');
        }

        return $next($request);
    }
}
