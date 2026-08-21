<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $tenant = $user->tenant;
        $owner = $user->owner;

        $units = collect();
        $properties = collect();

        if ($tenant) {
            $units = $tenant->units()->with(['property.owner'])->get();
        }

        if ($owner) {
            $properties = $owner->properties()->with(['units.currentTenant'])->get();
        }

        return view('portal.units.index', compact('user', 'tenant', 'owner', 'units', 'properties'));
    }
}
