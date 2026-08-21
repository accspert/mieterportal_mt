<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\Unit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class KundenportalHealthCheck extends Command
{
    protected $signature = 'kundenportal:health';

    protected $description = 'Perform a comprehensive health check and diagnostic inspection of the Kundenportal application.';

    public function handle(): int
    {
        $this->info('======================================================');
        $this->info('       KUNDENPORTAL — SYSTEM HEALTH CHECK            ');
        $this->info('======================================================');
        $this->newLine();

        $allOk = true;

        // 1. Database Connectivity
        $this->comment('1. Datenbank-Verbindung & Status:');
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            $this->line("   [OK] Datenbank erreichbar (Verbindung: {$dbName})");
        } catch (\Throwable $e) {
            $this->error("   [FEHLER] Datenbank nicht erreichbar: {$e->getMessage()}");
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

        // 3. Security & Roles
        $this->newLine();
        $this->comment('3. Rollen- & Berechtigungssystem:');
        $expectedRoles = ['Administrator', 'Sachbearbeiter', 'Eigentümer', 'Mieter'];
        $existingRoles = Role::pluck('name')->toArray();
        $missingRoles = array_diff($expectedRoles, $existingRoles);

        if (empty($missingRoles)) {
            $this->line('   [OK] Alle Standard-Rollen vorhanden ('.implode(', ', $expectedRoles).')');
        } else {
            $this->warn('   [WARNUNG] Fehlende Rollen: '.implode(', ', $missingRoles));
            $allOk = false;
        }

        // 4. Operational KPIs & Domain Summary
        $this->newLine();
        $this->comment('4. Operative Kennzahlen & Datenbestand:');
        $propertiesCount = Property::count();
        $unitsCount = Unit::count();
        $occupiedUnits = Unit::whereNotNull('current_tenant_id')->count();
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
