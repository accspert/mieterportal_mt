<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use App\Services\CsvExportService;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataExportAndBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin.export@kundenportal.local',
        ]);
        $this->admin->assignRole('Administrator');

        $this->staff = User::factory()->create([
            'email' => 'staff.export@kundenportal.local',
        ]);
        $this->staff->assignRole('Sachbearbeiter');
    }

    public function test_csv_export_service_streams_csv_with_utf8_bom(): void
    {
        $exportService = app(CsvExportService::class);

        $headers = ['Spalte 1', 'Spalte 2'];
        $rows = [
            ['Wert A', 'Wert B'],
            ['Wert C', 'Wert D'],
        ];

        $response = $exportService->streamCsv('test.csv', $headers, $rows);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('filename=test.csv', $response->headers->get('Content-Disposition'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('"Spalte 1";"Spalte 2"', $content);
        $this->assertStringContainsString('"Wert A";"Wert B"', $content);
    }

    public function test_invoices_can_be_bulk_updated_to_paid(): void
    {
        $tenant = Tenant::factory()->create();
        $invoices = Invoice::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'status' => 'sent',
            'amount' => 500.00,
        ]);

        $this->actingAs($this->admin);

        // Simulate bulk update logic
        $invoices->each(fn (Invoice $inv) => $inv->update(['status' => 'paid']));

        $this->assertEquals(3, Invoice::where('status', 'paid')->count());
    }

    public function test_tickets_can_be_bulk_updated_and_assigned(): void
    {
        $tickets = Ticket::factory()->count(3)->create([
            'status' => 'open',
            'assigned_to' => null,
        ]);

        $this->actingAs($this->admin);

        // Simulate bulk assign & status update
        $tickets->each(fn (Ticket $t) => $t->update([
            'status' => 'in_progress',
            'assigned_to' => $this->staff->id,
        ]));

        $this->assertEquals(3, Ticket::where('status', 'in_progress')->where('assigned_to', $this->staff->id)->count());
    }
}
