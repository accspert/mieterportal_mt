<?php

declare(strict_types=1);

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\DocumentController;
use App\Http\Controllers\Portal\InvoiceController;
use App\Http\Controllers\Portal\MessageController;
use App\Http\Controllers\Portal\NotificationController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\TicketController;
use App\Http\Controllers\Portal\UnitController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| These routes are loaded within tenant context (subdomain or custom domain).
| Each request is bound to the isolated database and storage of the tenant.
|
*/

Route::middleware([
    InitializeTenancyByDomainOrSubdomain::class,
    PreventAccessFromCentralDomains::class,
    'web',
])->group(function () {

    Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

    Route::get('/', function () {
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->hasRole('Administrator') || $user->hasRole('Sachbearbeiter')) {
                return redirect(config('filament.path', '/admin'));
            }

            return redirect()->route('portal.dashboard');
        }

        return redirect()->route('portal.login');
    });

    // Default login alias for tenant auth
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');

    // Customer / Tenant Portal Routes
    Route::prefix('portal')->name('portal.')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.post');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // Authenticated Routes inside Tenant DB
        Route::middleware('auth')->group(function () {
            Route::get('/', fn () => redirect()->route('portal.dashboard'));
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('units', [UnitController::class, 'index'])->name('units.index');

            // Tickets & Damage Reports
            Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
            Route::get('tickets/create', [TicketController::class, 'create'])->name('tickets.create');
            Route::post('tickets', [TicketController::class, 'store'])->name('tickets.store');
            Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

            // Documents (Mietverträge, etc.)
            Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
            Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

            // Invoices & Utility Bills
            Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');

            // Messages
            Route::get('messages', [MessageController::class, 'index'])->name('messages.index');
            Route::post('messages', [MessageController::class, 'store'])->name('messages.store');

            // Notifications
            Route::post('notifications/mark-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markRead');

            // Profile & Settings
            Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
            Route::put('profile/contact', [ProfileController::class, 'updateContact'])->name('profile.contact');
            Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        });
    });
});
