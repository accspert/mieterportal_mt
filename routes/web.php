<?php

use App\Http\Controllers\Central\LoginController;
use App\Http\Controllers\Central\RegistrationController;
use App\Http\Controllers\Central\SeoController;
use App\Http\Controllers\Central\TwoFactorChallengeController;
use App\Http\Controllers\LocaleController;
use App\Services\ImpersonationService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central Routes
|--------------------------------------------------------------------------
|
| Public website, login and registration. The portal routes are in routes/tenant.php;
| all property managements share this one domain and one database.
|
*/

Route::get('locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::view('/', 'welcome-central')->name('central.home');

// The single login of the platform; the account determines the property management.
Route::get('anmelden', [LoginController::class, 'show'])->name('central.login');
Route::post('anmelden', [LoginController::class, 'login'])->middleware('throttle:portal-login')->name('central.login.attempt');
Route::get('anmelden/bestaetigen', [TwoFactorChallengeController::class, 'show'])->name('auth.challenge');
Route::post('anmelden/bestaetigen', [TwoFactorChallengeController::class, 'verify'])->middleware('throttle:6,1')->name('auth.challenge.verify');
Route::post('anmelden/bestaetigen/neuer-code', [TwoFactorChallengeController::class, 'resend'])->middleware('throttle:3,1')->name('auth.challenge.resend');
Route::get('passwort-vergessen', [LoginController::class, 'showForgotPassword'])->name('central.password.request');
Route::post('passwort-vergessen', [LoginController::class, 'sendPasswordResetLink'])->middleware('throttle:portal-login')->name('central.password.email');

// Super admin support mode: return from the account of a user to the backoffice (/superadmin).
Route::get('betrieb/zurueck', fn (ImpersonationService $impersonation) => redirect()->to($impersonation->stop()))
    ->middleware('auth')
    ->name('impersonation.stop');

// Self-service sign-up of property managements
Route::get('registrieren', [RegistrationController::class, 'create'])->name('central.register');
Route::post('registrieren', [RegistrationController::class, 'store'])->middleware('throttle:5,60')->name('central.register.store');
Route::get('registrieren/gesendet', [RegistrationController::class, 'sent'])->name('central.register.sent');
Route::get('registrieren/bestaetigen/{registration}', [RegistrationController::class, 'confirm'])->middleware('signed')->name('central.register.confirm');

// Search engines: only the central domain is public (tenant domains get "Disallow: /").
Route::get('robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');

// Legal pages – deliberately without tenancy middleware so they are reachable on central and tenant domains alike.
Route::view('impressum', 'legal.imprint')->name('legal.imprint');
Route::view('datenschutz', 'legal.privacy')->name('legal.privacy');
Route::view('agb', 'legal.terms')->name('legal.terms');
