<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use App\Services\FileUploadService;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoicePdfAndUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenantUser;

    protected Tenant $tenant;

    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);

        $this->tenantUser = User::factory()->create([
            'email' => 'tenant.pdf@kundenportal.local',
        ]);
        $this->tenantUser->assignRole('Mieter');

        $this->tenant = Tenant::factory()->create([
            'user_id' => $this->tenantUser->id,
            'first_name' => 'Anna',
            'last_name' => 'Schmidt',
        ]);

        $owner = Owner::factory()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $unit = Unit::factory()->create([
            'property_id' => $property->id,
            'current_tenant_id' => $this->tenant->id,
            'number' => '204',
        ]);

        $this->invoice = Invoice::factory()->create([
            'invoice_number' => 'RE-2026-PDF-001',
            'tenant_id' => $this->tenant->id,
            'unit_id' => $unit->id,
            'owner_id' => $owner->id,
            'amount' => 820.00,
            'due_date' => '2026-11-01',
            'status' => 'sent',
        ]);
    }

    public function test_tenant_can_download_invoice_pdf_via_portal(): void
    {
        $response = $this->actingAs($this->tenantUser)->get('/portal/invoices/'.$this->invoice->id.'/download');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_unauthorized_user_cannot_download_other_invoice_pdf(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('Mieter');

        $response = $this->actingAs($otherUser)->get('/portal/invoices/'.$this->invoice->id.'/download');

        $response->assertStatus(403);
    }

    public function test_api_pdf_download_is_restricted_to_authorized_users(): void
    {
        $this->invoice->update(['renter_id' => $this->tenant->id]);

        $otherRenterUser = User::factory()->create();
        $otherRenterUser->assignRole('Mieter');
        Renter::factory()->create(['user_id' => $otherRenterUser->id]);

        Sanctum::actingAs($otherRenterUser);
        $this->get('/api/invoices/'.$this->invoice->id.'/pdf')->assertForbidden();

        Sanctum::actingAs($this->tenantUser);
        $this->get('/api/invoices/'.$this->invoice->id.'/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_api_client_can_download_invoice_pdf(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrator');
        $token = $admin->createToken('admin-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get('/api/invoices/'.$this->invoice->id.'/pdf');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_tenant_can_create_ticket_with_file_attachments(): void
    {
        Storage::fake('public');

        $file1 = UploadedFile::fake()->image('wasserschaden_bad.jpg', 600, 400);
        $file2 = UploadedFile::fake()->create('reparatur_angebot.pdf', 150, 'application/pdf');

        $response = $this->actingAs($this->tenantUser)->post('/portal/tickets', [
            'title' => 'Wasserschaden Decke',
            'description' => 'Feuchter Fleck an der Badezimmerdecke entdeckt.',
            'category' => 'repair',
            'priority' => 'high',
            'attachments' => [$file1, $file2],
        ]);

        $ticket = Ticket::where('title', 'Wasserschaden Decke')->first();
        $this->assertNotNull($ticket);
        $response->assertRedirect(route('portal.tickets.show', $ticket));

        $this->assertCount(2, $ticket->attachments);

        $attachment1 = $ticket->attachments->first();
        $this->assertEquals('wasserschaden_bad.jpg', $attachment1->filename);
        Storage::disk('public')->assertExists($attachment1->path);

        $attachment2 = $ticket->attachments->last();
        $this->assertEquals('reparatur_angebot.pdf', $attachment2->filename);
        Storage::disk('public')->assertExists($attachment2->path);
    }

    public function test_portal_rejects_attachments_with_php_file_name(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('foto.png');
        $disguisedImage = new UploadedFile($image->getRealPath(), 'schaden.php', null, null, true);

        $this->actingAs($this->tenantUser)->post('/portal/tickets', [
            'title' => 'Getarnter Upload',
            'description' => 'Bild mit PHP-Endung.',
            'category' => 'repair',
            'priority' => 'low',
            'attachments' => [$disguisedImage],
        ])->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseMissing('tickets', ['title' => 'Getarnter Upload']);
    }

    public function test_upload_service_derives_stored_extension_from_file_content_not_client_name(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('foto.png');
        $disguisedImage = new UploadedFile($image->getRealPath(), 'schaden.phtml', null, null, true);
        $ticket = Ticket::factory()->create();

        $attachment = app(FileUploadService::class)->storeAttachment($disguisedImage, $ticket, $this->tenantUser);

        $this->assertStringEndsWith('.png', $attachment->path);
        $this->assertSame('schaden.phtml', $attachment->filename);
        Storage::disk('public')->assertExists($attachment->path);
    }
}
