<?php

namespace App\Console\Commands;

use App\Models\CompanyTenant;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\Unit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KundenportalHealthCheck extends Command
{
    protected $signature = 'mieterportal:health {--tenant= : Check a specific property management}';

    protected $aliases = ['kundenportal:health'];

    protected $description = 'Perform a comprehensive health check and diagnostic inspection of the Mieterportal application.';

    public function handle(): int
    {
        $this->info('======================================================');
        $this->info('       MIETERPORTAL — SYSTEM HEALTH CHECK            ');
        $this->info('======================================================');
        $this->newLine();

        $allOk = true;

        // 1. Central Database Connectivity
        $this->comment('1. Zentrale Datenbank-Verbindung & Mandanten:');
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            $tenantCount = CompanyTenant::count();
            $this->line("   [OK] Zentrale Datenbank erreichbar ({$dbName}) — {$tenantCount} SaaS-Mandanten registriert");
        } catch (\Throwable $e) {
            $this->error("   [FEHLER] Zentrale Datenbank nicht erreichbar: {$e->getMessage()}");
            $allOk = false;
        }

        // 2. Storage System
        $this->newLine();
        $this->comment('2. Dateispeicher & Berechtigungen:');
        try {
            $testFile = 'health_check_'.time().'.tmp';
            Storage::disk('public')->put($testFile, 'health-check');
            Storage::disk('public')->delete($testFile);
            $this->line('   [OK] Public-Speicher beschreib- und lesbar (storage/app/public)');
        } catch (\Throwable $e) {
            $this->error("   [FEHLER] Public-Speicher Schreibfehler: {$e->getMessage()}");
            $allOk = false;
        }

        // 3. Key figures per property management (all share one database)
        $this->newLine();
        $this->comment('3. Mandanten & Kennzahlen:');

        $tenantQuery = CompanyTenant::query();
        if ($tenantId = $this->option('tenant')) {
            $tenantQuery->where('id', $tenantId);
        }

        $tenants = $tenantQuery->get();

        if ($tenants->isEmpty()) {
            $this->warn('   [WARNUNG] Keine passenden Mandanten gefunden.');
        }

        foreach ($tenants as $tenant) {
            $this->line("   --- Mandant: <info>{$tenant->id}</info> ---");
            $tenant->run(function () use (&$allOk) {
                try {
                    $propertiesCount = Property::count();
                    $unitsCount = Unit::count();
                    $occupiedUnits = Unit::whereNotNull('current_renter_id')->count();
                    $openTickets = Ticket::whereIn('status', ['open', 'in_progress', 'waiting'])->count();
                    $unpaidInvoices = Invoice::whereIn('status', ['sent', 'overdue'])->count();
                    $unpaidAmount = Invoice::whereIn('status', ['sent', 'overdue'])->sum('amount');

                    $this->table(
                        ['Metrik / Bereich', 'Wert'],
                        [
                            ['Liegenschaften / Objekte', $propertiesCount],
                            ['Einheiten (Gesamt)', $unitsCount],
                            ['Vermietete Einheiten', "{$occupiedUnits} (".($unitsCount > 0 ? round(($occupiedUnits / $unitsCount) * 100, 1) : 0).'%)'],
                            ['Offene Service-Tickets', $openTickets],
                            ['Ausstehende Rechnungen', "{$unpaidInvoices} (".number_format($unpaidAmount, 2, ',', '.').' €)'],
                        ]
                    );
                } catch (\Throwable $e) {
                    $this->error("   [FEHLER] Mandanten-Prüfung fehlgeschlagen: {$e->getMessage()}");
                    $allOk = false;
                }
            });
        }

        $this->newLine();
        if ($allOk) {
            $this->info('>> Alle Systemprüfungen erfolgreich abgeschlossen. System ist betriebsbereit.');

            return Command::SUCCESS;
        } else {
            $this->warn('>> Einige Systemprüfungen haben Warnungen oder Fehler ergeben.');

            return Command::FAILURE;
        }
    }
}
