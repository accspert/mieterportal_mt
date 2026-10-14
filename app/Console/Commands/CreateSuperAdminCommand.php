<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CentralLoginService;
use App\Services\PortalAccessService;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Creates the account of a platform operator (super admin). The person receives an invitation and
 * sets the password personally, so no password ever appears in the terminal or a shell history.
 */
class CreateSuperAdminCommand extends Command
{
    protected $signature = 'superadmin:create
                            {email : E-mail address of the super admin}
                            {--name=Plattform-Betrieb : Display name}';

    protected $description = 'Create a super admin account (backoffice /superadmin) and send the invitation';

    public function handle(PortalAccessService $portalAccess): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));

        if (User::withoutGlobalScopes()->where('is_super_admin', true)->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            $this->info("{$email} ist bereits Superadmin.");

            return self::SUCCESS;
        }

        try {
            $portalAccess->inviteSuperAdmin((string) $this->option('name'), $email);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Superadmin {$email} angelegt. Die Einladung zum Festlegen des Passworts wurde per E-Mail gesendet.");
        $this->line('Danach anmelden unter: '.CentralLoginService::loginUrl().' (weiter zu /superadmin)');

        return self::SUCCESS;
    }
}
