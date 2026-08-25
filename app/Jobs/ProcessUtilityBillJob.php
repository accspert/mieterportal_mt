<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Unit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stancl\Tenancy\Contracts\TenantAwareJob;

class ProcessUtilityBillJob implements ShouldQueue, TenantAwareJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $billingYear,
        public ?int $unitId = null
    ) {
        $this->onQueue('utility-bills');
    }

    public function handle(): void
    {
        // Läuft automatisch im isolierten Mandanten-Kontext der Hausverwaltung!
        $units = Unit::query()
            ->when($this->unitId, fn ($q) => $q->where('id', $this->unitId))
            ->get();

        foreach ($units as $unit) {
            // Generierung von Abrechnungsdokumenten, S3-Speicherung & Versand
        }
    }
}
