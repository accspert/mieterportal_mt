<?php

declare(strict_types=1);

use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\DocumentController;
use App\Http\Controllers\Portal\InvoiceController;
use App\Http\Controllers\Portal\MessageController;
use App\Http\Controllers\Portal\NotificationController;
use App\Http\Controllers\Portal\PasswordResetController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\TicketController;
use App\Http\Controllers\Portal\UnitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal Routes
|--------------------------------------------------------------------------
|
| The portal of renters and owners. All property managements share one domain and one
| database; the 'web' group activates the property management of the logged-in user
| (InitializeTenancyFromUser), which limits every query to its records.
|
*/

Route::middleware('web')->group(function () {
    // There is only one login (central.login); 'login' is where unauthenticated users are sent.
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');

    Route::prefix('portal')->name('portal.')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // Password reset & invitation links from the mails
        Route::get('password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
        Route::post('password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:portal-login')->name('password.update');

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
