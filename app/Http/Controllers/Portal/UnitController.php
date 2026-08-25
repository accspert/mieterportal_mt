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

        $renter = $user->renter;
        $tenant = $renter;
        $owner = $user->owner;

        $units = collect();
        $properties = collect();

        if ($renter) {
            $units = $renter->units()->with(['property.owner'])->get();
        }

        if ($owner) {
            $properties = $owner->properties()->with(['units.currentRenter'])->get();
        }

        return view('portal.units.index', compact('user', 'tenant', 'owner', 'units', 'properties'));
    }
}
