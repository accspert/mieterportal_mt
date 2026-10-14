<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CompanyTenant;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrateTenantStorageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:migrate-storage
                            {--tenant= : Only migrate the files of this tenant id}
                            {--dry-run : List the files that would be copied without copying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy files from the formerly shared public disk into the isolated storage of the tenant that references them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sharedDisk = Storage::build([
            'driver' => 'local',
            'root' => config('filesystems.disks.public.root'),
        ]);

        $tenants = CompanyTenant::query()
            ->when($this->option('tenant'), fn ($query, string $tenantId) => $query->whereKey($tenantId))
            ->get();

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($tenant, $sharedDisk): void {
                $this->migrateTenantFiles($tenant, $sharedDisk);
            });
        }

        return self::SUCCESS;
    }

    protected function migrateTenantFiles(CompanyTenant $tenant, Filesystem $sharedDisk): void
    {
        $tenantDisk = Storage::disk('public');
        $copied = 0;
        $missing = 0;

        foreach ($this->referencedPaths() as $path) {
            if ($tenantDisk->exists($path)) {
                continue;
            }

            if (! $sharedDisk->exists($path)) {
                $missing++;
                $this->warn("   [{$tenant->id}] Datei nicht gefunden: {$path}");

                continue;
            }

            if (! $this->option('dry-run')) {
                $tenantDisk->writeStream($path, $sharedDisk->readStream($path));
            }

            $copied++;
        }

        $verb = $this->option('dry-run') ? 'würden kopiert' : 'kopiert';
        $this->info("[{$tenant->id}] {$copied} Dateien {$verb}, {$missing} fehlend.");
    }

    /**
     * @return Collection<int, string>
     */
    protected function referencedPaths(): Collection
    {
        // Raw queries bypass the model scope: limit them to the property management being migrated.
        $tenantKey = tenant()->getTenantKey();

        return DB::table('documents')->where('company_tenant_id', $tenantKey)->pluck('path')
            ->merge(DB::table('attachments')->where('company_tenant_id', $tenantKey)->pluck('path'))
            ->merge(DB::table('invoices')->where('company_tenant_id', $tenantKey)->pluck('pdf_path'))
            ->filter()
            ->unique()
            ->values();
    }
}
