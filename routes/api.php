<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Central\TenantRegistrationController;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Http\Middleware\InitializeTenancyFromUser;
use Illuminate\Support\Facades\Route;

// Tenant onboarding – requires the TENANT_ONBOARDING_TOKEN as bearer token (see StoreTenantRegistrationRequest)
Route::post('onboard', [TenantRegistrationController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('tenants.onboard');

// Public auth route – strictly rate-limited to protect against brute-force attacks.
// There is no self-registration: users are created by the property management.
Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// The token's user determines the property management whose data the request may see.
Route::middleware(['auth:sanctum', InitializeTenancyFromUser::class, EnsureTenantIsActive::class, 'throttle:60,1'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    Route::apiResource('properties', PropertyController::class);
    Route::apiResource('units', UnitController::class);
    Route::apiResource('tickets', TicketController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);
    Route::apiResource('tasks', TaskController::class);
    Route::apiResource('documents', DocumentController::class);
    Route::apiResource('messages', MessageController::class);
    Route::patch('messages/{message}/read', [MessageController::class, 'markAsRead']);
    Route::apiResource('announcements', AnnouncementController::class);
});
