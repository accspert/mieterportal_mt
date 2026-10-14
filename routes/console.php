<?php

use App\Models\TenantRegistration;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks (cron: * * * * * php artisan schedule:run)
|--------------------------------------------------------------------------
*/

// Backups: the shared database + uploaded files, then retention cleanup and health monitoring.
Schedule::command('backup:clean')->dailyAt('01:30')->onOneServer();
Schedule::command('backup:run')->dailyAt('02:00')->onOneServer()->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('08:00')->onOneServer();

// Unconfirmed self-service sign-ups are deleted after a week.
Schedule::command('model:prune', ['--model' => [TenantRegistration::class]])->daily()->onOneServer();

// Housekeeping: expired password reset and invitation tokens, old audit entries.
Schedule::command('auth:clear-resets')->daily()->onOneServer();
Schedule::command('auth:clear-resets invitations')->daily()->onOneServer();
Schedule::command('activitylog:clean')->weekly()->onOneServer();
