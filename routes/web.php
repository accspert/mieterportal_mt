<?php

use App\Http\Controllers\Central\TenantRegistrationController;
use App\Http\Controllers\LocaleController;
use App\Models\CompanyTenant;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central Routes
|--------------------------------------------------------------------------
|
| These routes are loaded on central domains (e.g. localhost, 127.0.0.1, mieterportal-demo.eu).
| Tenant requests are handled isolated in routes/tenant.php.
|
*/

Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/', function () {
    $tenants = CompanyTenant::with('domains')->get();

    return view('welcome-central', [
        'tenants' => $tenants,
    ]);
})->name('central.home');

Route::post('api/onboard', [TenantRegistrationController::class, 'store'])->name('tenants.onboard');
