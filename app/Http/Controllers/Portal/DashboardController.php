<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ResolvesUserProfile;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesUserProfile;

    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $renter = $this->resolveRenter($user);
        $owner = $this->resolveOwner($user);

        $data = $this->dashboardService->dataFor($user, $renter, $owner);

        return view('portal.dashboard', array_merge($data, [
            'user' => $user,
            // Keep $tenant as a backward-compatible alias for the view until views are migrated.
            'tenant' => $data['renter'],
        ]));
    }
}
