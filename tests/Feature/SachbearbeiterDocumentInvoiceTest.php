<?php

namespace Tests\Feature;

use App\Filament\Resources\TenantResource\Pages\EditTenant;
use App\Filament\Resources\TenantResource\Pages\ListTenants;
use App\Filament\Resources\TenantResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\TenantResource\RelationManagers\InvoicesRelationManager;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SachbearbeiterDocumentInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;

    protected User $tenantUser;

    protected Tenant $tenant;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);

        $this->staff = User::factory()->create([
            'email' => 'sachbearbeiter@test.local',
            'password' => bcrypt('password'),
        ]);
        $this->staff->assignRole('Sachbearbeiter');

        $this->tenantUser = User::factory()->create([
            'email' => 'mieter@test.local',
        ]);
        $this->tenantUser->assignRole('Mieter');

        $this->tenant = Tenant::factory()->create([
            'user_id' => $this->tenantUser->id,
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'email' => 'mieter@test.local',
        ]);

        $owner = Owner::factory()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $this->unit = Unit::factory()->create([
            'property_id' => $property->id,
            'current_tenant_id' => $this->tenant->id,
            'number' => '101',
        ]);
    }

    public function test_sachbearbeiter_can_access_tenant_edit_page_with_relation_managers(): void
    {
        $this->actingAs($this->staff)
            ->get("/admin/tenants/{$this->tenant->id}/edit")
            ->assertStatus(200);
    }

    public function test_sachbearbeiter_can_upload_document_for_tenant_via_table_action(): void
    {
        Storage::fake('public');
        $fakeFile = UploadedFile::fake()->create('Mietvertrag_Max_Mustermann.pdf', 200, 'application/pdf');

        Livewire::actingAs($this->staff)
            ->test(ListTenants::class)
            ->callTableAction('uploadDocument', $this->tenant, data: [
                'filename' => 'Mietvertrag_Max_Mustermann.pdf',
                'category' => 'contract',
                'path' => $fakeFile,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('documents', [
            'filename' => 'Mietvertrag_Max_Mustermann.pdf',
            'category' => 'contract',
            'documentable_type' => Tenant::class,
            'documentable_id' => $this->tenant->id,
            'uploaded_by' => $this->staff->id,
        ]);
    }

    public function test_sachbearbeiter_can_create_invoice_for_tenant_via_table_action(): void
    {
        Storage::fake('public');
        $fakePdf = UploadedFile::fake()->create('Nebenkosten_2025.pdf', 150, 'application/pdf');

        Livewire::actingAs($this->staff)
            ->test(ListTenants::class)
            ->callTableAction('createInvoice', $this->tenant, data: [
                'invoice_number' => 'RE-2026-TEST-999',
                'unit_id' => $this->unit->id,
                'amount' => '320.00',
                'due_date' => '2026-10-15',
                'status' => 'sent',
                'pdf_path' => $fakePdf,
                'notes' => 'Nebenkostenabrechnung 2025 Nachzahlung',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'RE-2026-TEST-999',
            'tenant_id' => $this->tenant->id,
            'amount' => 320.00,
            'status' => 'sent',
        ]);
    }

    public function test_sachbearbeiter_can_upload_document_via_relation_manager(): void
    {
        Storage::fake('public');
        $fakeFile = UploadedFile::fake()->create('Wohnungsuebergabe.pdf', 200, 'application/pdf');

        Livewire::actingAs($this->staff)
            ->test(DocumentsRelationManager::class, [
                'ownerRecord' => $this->tenant,
                'pageClass' => EditTenant::class,
            ])
            ->callTableAction('create', data: [
                'filename' => 'Wohnungsuebergabe.pdf',
                'category' => 'protocol',
                'path' => $fakeFile,
                'version' => 1,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('documents', [
            'filename' => 'Wohnungsuebergabe.pdf',
            'category' => 'protocol',
            'documentable_type' => Tenant::class,
            'documentable_id' => $this->tenant->id,
            'uploaded_by' => $this->staff->id,
        ]);
    }

    public function test_sachbearbeiter_can_create_invoice_via_relation_manager(): void
    {
        Livewire::actingAs($this->staff)
            ->test(InvoicesRelationManager::class, [
                'ownerRecord' => $this->tenant,
                'pageClass' => EditTenant::class,
            ])
            ->callTableAction('create', data: [
                'invoice_number' => 'RE-2026-REL-001',
                'unit_id' => $this->unit->id,
                'amount' => '450.00',
                'due_date' => '2026-11-01',
                'status' => 'sent',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'RE-2026-REL-001',
            'tenant_id' => $this->tenant->id,
            'amount' => 450.00,
            'status' => 'sent',
        ]);
    }

    public function test_sachbearbeiter_has_full_access_to_document_and_invoice_resources(): void
    {
        $this->actingAs($this->staff)->get('/admin/documents')->assertStatus(200);
        $this->actingAs($this->staff)->get('/admin/documents/create')->assertStatus(200);
        $this->actingAs($this->staff)->get('/admin/invoices')->assertStatus(200);
        $this->actingAs($this->staff)->get('/admin/invoices/create')->assertStatus(200);
    }
}
