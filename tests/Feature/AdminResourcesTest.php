<?php

namespace Tests\Feature;

use App\Filament\Resources\DocumentResource\Pages\CreateDocument;
use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\MessageResource\Pages\CreateMessage;
use App\Filament\Resources\OwnerResource\Pages\CreateOwner;
use App\Filament\Resources\TaskResource\Pages\CreateTask;
use App\Filament\Resources\TicketResource\Pages\CreateTicket;
use App\Filament\Resources\UserResource\Pages\CreateUser;
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

class AdminResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin@kundenportal.local',
            'password' => bcrypt('password'),
        ]);
        $this->admin->assignRole('Administrator');
    }

    public function test_admin_can_view_all_resource_index_pages(): void
    {
        $this->actingAs($this->admin)->get('/admin/owners')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/properties')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/units')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/tenants')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/tickets')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/documents')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/messages')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/tasks')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/invoices')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/users')->assertStatus(200);
    }

    public function test_admin_can_view_all_resource_create_pages(): void
    {
        $this->actingAs($this->admin)->get('/admin/owners/create')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/properties/create')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/units/create')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/tenants/create')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/tickets/create')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/documents/create')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/messages/create')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/tasks/create')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/invoices/create')->assertStatus(200);
        $this->actingAs($this->admin)->get('/admin/users/create')->assertStatus(200);
    }

    public function test_admin_can_view_property_edit_page_with_relation_manager(): void
    {
        $property = Property::factory()->create();

        $this->actingAs($this->admin)
            ->get("/admin/properties/{$property->id}/edit")
            ->assertStatus(200);
    }

    public function test_admin_can_create_ticket_via_filament(): void
    {
        $owner = Owner::factory()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($this->admin);

        Livewire::test(CreateTicket::class)
            ->fillForm([
                'title' => 'Heizung ausgefallen',
                'description' => 'Die Heizung im 2. OG funktioniert nicht mehr.',
                'category' => 'repair',
                'priority' => 'high',
                'status' => 'open',
                'property_id' => $property->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tickets', [
            'title' => 'Heizung ausgefallen',
            'category' => 'repair',
            'priority' => 'high',
            'property_id' => $property->id,
        ]);
    }

    public function test_admin_can_create_owner_via_filament(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateOwner::class)
            ->fillForm([
                'company_name' => 'Immobilien Nord GmbH',
                'first_name' => 'Klaus',
                'last_name' => 'Müller',
                'email' => 'mueller@immo-nord.local',
                'city' => 'Hamburg',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('owners', [
            'company_name' => 'Immobilien Nord GmbH',
            'email' => 'mueller@immo-nord.local',
        ]);
    }

    public function test_admin_can_create_document_via_filament(): void
    {
        $owner = Owner::factory()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($this->admin);

        Storage::fake('public');
        $fakeFile = UploadedFile::fake()->create('Mietvertrag_2026.pdf', 100, 'application/pdf');

        Livewire::test(CreateDocument::class)
            ->fillForm([
                'filename' => 'Mietvertrag_2026.pdf',
                'category' => 'contract',
                'documentable_type' => Property::class,
                'documentable_id' => $property->id,
                'path' => $fakeFile,
                'version' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('documents', [
            'filename' => 'Mietvertrag_2026.pdf',
            'category' => 'contract',
            'documentable_type' => Property::class,
            'documentable_id' => $property->id,
        ]);
    }

    public function test_admin_can_create_message_via_filament(): void
    {
        $recipient = User::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test(CreateMessage::class)
            ->fillForm([
                'from_user_id' => $this->admin->id,
                'to_user_id' => $recipient->id,
                'subject' => 'Wartungsankündigung Aufzug',
                'body' => 'Der Aufzug wird am kommenden Dienstag gewartet.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('messages', [
            'from_user_id' => $this->admin->id,
            'to_user_id' => $recipient->id,
            'subject' => 'Wartungsankündigung Aufzug',
        ]);
    }

    public function test_admin_can_create_task_via_filament(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test(CreateTask::class)
            ->fillForm([
                'title' => 'Objektbegehung durchführen',
                'description' => 'Jahresinspektion Außenanlagen',
                'priority' => 'high',
                'status' => 'pending',
                'assigned_to' => $staff->id,
                'created_by' => $this->admin->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Objektbegehung durchführen',
            'priority' => 'high',
            'assigned_to' => $staff->id,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_create_invoice_via_filament(): void
    {
        $owner = Owner::factory()->create();
        $property = Property::factory()->create(['owner_id' => $owner->id]);
        $unit = Unit::factory()->create(['property_id' => $property->id]);
        $tenant = Tenant::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test(CreateInvoice::class)
            ->fillForm([
                'invoice_number' => 'RE-2026-TEST-001',
                'tenant_id' => $tenant->id,
                'unit_id' => $unit->id,
                'amount' => '750.50',
                'due_date' => '2026-09-01',
                'status' => 'sent',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'RE-2026-TEST-001',
            'tenant_id' => $tenant->id,
            'amount' => 750.50,
            'status' => 'sent',
        ]);
    }

    public function test_admin_can_create_user_with_role_via_filament(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'New Staff User',
                'email' => 'newstaff@kundenportal.local',
                'password' => 'secret123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'newstaff@kundenportal.local',
        ]);
    }
}
