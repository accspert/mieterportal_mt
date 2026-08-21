<?php

namespace App\Providers;

use App\Http\Responses\LogoutResponse;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Ticket;
use App\Observers\DocumentObserver;
use App\Observers\InvoiceObserver;
use App\Observers\MessageObserver;
use App\Observers\TicketObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \Filament\Auth\Http\Responses\Contracts\LogoutResponse::class,
            LogoutResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Ticket::observe(TicketObserver::class);
        Message::observe(MessageObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Document::observe(DocumentObserver::class);
    }
}
