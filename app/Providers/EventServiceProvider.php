<?php

namespace App\Providers;

use App\Events\TicketCreated;
use App\Listeners\SendTicketNotifications;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TicketCreated::class => [
            SendTicketNotifications::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
