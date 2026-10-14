<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal\Concerns;

use App\Models\Owner;
use App\Models\Renter;
use App\Models\User;

/**
 * Provides a canonical way to resolve the Renter and Owner profiles for the
 * currently authenticated user.
 *
 * Both methods delegate to User::resolvedRenter() / User::resolvedOwner(),
 * which link profiles exclusively via the user_id foreign key.
 *
 * Usage in any Portal controller:
 *   use App\Http\Controllers\Portal\Concerns\ResolvesUserProfile;
 *
 *   class FooController extends Controller {
 *       use ResolvesUserProfile;
 *
 *       public function index(): View {
 *           $user   = Auth::user();
 *           $renter = $this->resolveRenter($user);
 *           $owner  = $this->resolveOwner($user);
 *       }
 *   }
 */
trait ResolvesUserProfile
{
    protected function resolveRenter(User $user): ?Renter
    {
        return $user->resolvedRenter();
    }

    protected function resolveOwner(User $user): ?Owner
    {
        return $user->resolvedOwner();
    }
}
