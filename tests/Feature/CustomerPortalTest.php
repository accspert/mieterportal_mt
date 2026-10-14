<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use App\Services\CentralLoginService;
use Database\Seeders\InitialSetupSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenantUser;

    protected Tenant $tenant;

    protected Property $property;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);

        $this->tenantUser = User::factory()->create([
            'email' => 'tenant.test@kundenportal.local',
            'password' => bcrypt('secret123'),
        ]);
        $this->tenantUser->assignRole('Mieter');

        $this->tenant = Tenant::factory()->create([
            'user_id' => $this->tenantUser->id,
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
        ]);

        $owner = Owner::factory()->create();
        $this->property = Property::factory()->create(['owner_id' => $owner->id]);
        $this->unit = Unit::factory()->create([
            'property_id' => $this->property->id,
            'current_tenant_id' => $this->tenant->id,
            'number' => '101',
        ]);
    }

    public function test_guest_is_redirected_to_portal_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Weniger Telefon.');
        $this->get('/portal/dashboard')->assertRedirect(route('login'));
        $this->get('/portal/tickets')->assertRedirect(route('login'));
    }

    public function test_portal_login_forwards_to_the_single_central_login(): void
    {
        $this->get('/portal/login')->assertRedirect(CentralLoginService::loginUrl());
    }

    public function test_central_login_never_shows_demo_credentials(): void
    {
        foreach ([false, true] as $isDemoMode) {
            config(['app.demo_mode' => $isDemoMode]);

            $this->get(route('central.login'))
                ->assertOk()
                ->assertDontSee('Demo-Zugänge')
                ->assertDontSee('kundenportal.local')
                ->assertDontSee('data-demo-email', false);
        }
    }

    public function test_tenant_can_access_dashboard(): void
    {
        $dashboard = $this->actingAs($this->tenantUser)->get('/portal/dashboard');
        $dashboard->assertStatus(200);
        $dashboard->assertSee('Guten Tag, '.$this->tenantUser->name);
    }

    public function test_tenant_can_view_units(): void
    {
        $response = $this->actingAs($this->tenantUser)->get('/portal/units');
        $response->assertStatus(200);
        $response->assertSee('Einheit 101');
        $response->assertSee($this->property->name);
    }

    public function test_tenant_can_create_ticket(): void
    {
        $response = $this->actingAs($this->tenantUser)->post('/portal/tickets', [
            'title' => 'Wasserhahn tropft in Küche',
            'description' => 'Der Einhebelmischer tropft kontinuierlich.',
            'category' => 'repair',
            'priority' => 'medium',
            'unit_id' => $this->unit->id,
        ]);

        $ticket = Ticket::where('title', 'Wasserhahn tropft in Küche')->first();
        $this->assertNotNull($ticket);
        $response->assertRedirect(route('portal.tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'title' => 'Wasserhahn tropft in Küche',
            'renter_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'created_by' => $this->tenantUser->id,
            'status' => 'open',
        ]);

        $detail = $this->actingAs($this->tenantUser)->get('/portal/tickets/'.$ticket->id);
        $detail->assertStatus(200);
        $detail->assertSee('Wasserhahn tropft in Küche');
    }

    public function test_tenant_can_view_documents(): void
    {
        Document::factory()->create([
            'filename' => 'Mietvertrag_Einheit_101.pdf',
            'category' => 'contract',
            'documentable_type' => Tenant::class,
            'documentable_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->tenantUser)->get('/portal/documents');
        $response->assertStatus(200);
        $response->assertSee('Mietvertrag_Einheit_101.pdf');
    }

    public function test_tenant_can_view_invoices(): void
    {
        Invoice::factory()->create([
            'invoice_number' => 'RE-2026-TEST-99',
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'amount' => 650.00,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($this->tenantUser)->get('/portal/invoices');
        $response->assertStatus(200);
        $response->assertSee('RE-2026-TEST-99');
        $response->assertSee('650,00 €');
    }

    public function test_tenant_can_send_message(): void
    {
        $response = $this->actingAs($this->tenantUser)->post('/portal/messages', [
            'subject' => 'Frage zur Mülltrennung',
            'body' => 'Gibt es zusätzliche Biotonnen für das Objekt?',
        ]);

        $response->assertRedirect(route('portal.messages.index'));
        $this->assertDatabaseHas('messages', [
            'from_user_id' => $this->tenantUser->id,
            'subject' => 'Frage zur Mülltrennung',
        ]);
    }

    public function test_owner_can_view_dashboard_and_properties(): void
    {
        $ownerUser = User::factory()->create();
        $ownerUser->assignRole('Eigentümer');

        $owner = Owner::factory()->create([
            'user_id' => $ownerUser->id,
            'company_name' => 'Hanse Immo Invest',
        ]);

        $ownerProperty = Property::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Elbchaussee Palais',
        ]);

        $response = $this->actingAs($ownerUser)->get('/portal/dashboard');
        $response->assertStatus(200);

        $unitsResponse = $this->actingAs($ownerUser)->get('/portal/units');
        $unitsResponse->assertStatus(200);
        $unitsResponse->assertSee('Elbchaussee Palais');
    }

    public function test_tenant_can_logout(): void
    {
        $response = $this->actingAs($this->tenantUser)->post('/portal/logout');
        $response->assertRedirect(CentralLoginService::loginUrl(['abgemeldet' => 1]));
        $this->assertGuest();
    }

    public function test_tenant_visiting_admin_is_redirected_to_portal_dashboard(): void
    {
        $response = $this->actingAs($this->tenantUser)->get('/admin');
        $response->assertRedirect(route('portal.dashboard'));
    }

    public function test_only_staff_can_access_admin_panel(): void
    {
        $panel = Filament::getPanel('admin');

        $staff = User::factory()->create();
        $staff->assignRole('Sachbearbeiter');

        $this->assertFalse($this->tenantUser->canAccessPanel($panel));
        $this->assertTrue($staff->canAccessPanel($panel));
    }

    public function test_central_login_is_rate_limited(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('central.login.attempt'), [
                'email' => $this->tenantUser->email,
                'password' => 'falsches-passwort',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('central.login.attempt'), [
            'email' => $this->tenantUser->email,
            'password' => 'falsches-passwort',
        ])->assertTooManyRequests();
    }
}
