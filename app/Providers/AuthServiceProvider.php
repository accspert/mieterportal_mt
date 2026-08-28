<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use App\Policies\AnnouncementPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\MessagePolicy;
use App\Policies\OwnerPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\RenterPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TicketPolicy;
use App\Policies\UnitPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Owner::class => OwnerPolicy::class,
        Property::class => PropertyPolicy::class,
        Unit::class => UnitPolicy::class,
        Renter::class => RenterPolicy::class,
        Document::class => DocumentPolicy::class,
        Ticket::class => TicketPolicy::class,
        Message::class => MessagePolicy::class,
        Task::class => TaskPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Announcement::class => AnnouncementPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Global admin override: Administrators bypass policy checks
        Gate::before(function (User $user, $ability) {
            if (method_exists($user, 'hasRole') && $user->hasRole('Administrator')) {
                return true;
            }

            return null;
        });
    }
}
