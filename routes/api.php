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
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
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
