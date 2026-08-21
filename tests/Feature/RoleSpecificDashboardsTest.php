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
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSpecificDashboardsTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenantUser;

    protected Tenant $tenant;

    protected User $ownerUser;

    protected Owner $owner;

    protected Property $property;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InitialSetupSeeder::class);

        // Create Property & Unit
        $this->property = Property::factory()->create([
            'name' => 'Elbterrasse Hamburg',
            'street' => 'Strandpromenade 10',
            'city' => 'Hamburg',
            'postal_code' => '20457',
        ]);

        $this->unit = Unit::factory()->create([
            'property_id' => $this->property->id,
            'number' => 'Wohnung 2. OG Links',
            'floor' => '2. OG',
            'area' => 85.5,
            'rooms' => 3,
        ]);

        // Create Tenant & User
        $this->tenantUser = User::factory()->create([
            'name' => 'Anna Mieterin',
            'email' => 'anna.dash@kundenportal.local',
        ]);
        $this->tenantUser->assignRole('Mieter');

        $this->tenant = Tenant::factory()->create([
            'user_id' => $this->tenantUser->id,
            'first_name' => 'Anna',
            'last_name' => 'Mieterin',
            'email' => 'anna.dash@kundenportal.local',
            'move_in' => now()->subMonths(6),
        ]);

        $this->unit->update(['current_tenant_id' => $this->tenant->id]);

        // Create Owner & User
        $this->ownerUser = User::factory()->create([
            'name' => 'Dr. Thomas Eigentuemer',
            'email' => 'thomas.owner@kundenportal.local',
        ]);
        $this->ownerUser->assignRole('Eigentümer');

        $this->owner = Owner::factory()->create([
            'user_id' => $this->ownerUser->id,
            'company_name' => 'Hanseatic Real Estate Holding',
            'first_name' => 'Thomas',
            'last_name' => 'Eigentuemer',
        ]);

        $this->property->update(['owner_id' => $this->owner->id]);
    }

    public function test_tenant_dashboard_displays_apartment_lease_and_invoices(): void
    {
        // Add a ticket for tenant
        $ticket = Ticket::factory()->create([
            'tenant_id' => $this->tenant->id,
            'property_id' => $this->property->id,
            'unit_id' => $this->unit->id,
            'title' => 'Heizkoerper defekt im Schlafzimmer',
            'status' => 'open',
        ]);

        // Add an invoice for tenant
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'MIETE-2026-08',
            'amount' => 950.00,
            'status' => 'sent',
        ]);

        // Add a document (lease contract)
        $doc = Document::factory()->create([
            'documentable_type' => Tenant::class,
            'documentable_id' => $this->tenant->id,
            'filename' => 'Mietvertrag Hauptwohnung.pdf',
            'category' => 'contract',
        ]);

        $response = $this->actingAs($this->tenantUser)->get('/portal/dashboard');

        $response->assertStatus(200);

        // Apartment & Lease
        $response->assertSee('Ihre Mieteinheit');
        $response->assertSee('Elbterrasse Hamburg — Wohnung 2. OG Links');
        $response->assertSee('85,5 m²');
        $response->assertSee('Mietvertrag (PDF)');

        // Tickets & Invoices
        $response->assertSee('Heizkoerper defekt im Schlafzimmer');
        $response->assertSee('+ Schaden melden');
        $response->assertSee('MIETE-2026-08');
        $response->assertSee('950,00 €');

        // Should NOT see owner portfolio grid
        $response->assertDontSee('Meine Liegenschaften', false);
        $response->assertDontSee('Vermietungsquote');
    }

    public function test_owner_dashboard_displays_portfolio_occupancy_and_all_tickets(): void
    {
        // Add another unit to check occupancy rate
        Unit::factory()->create([
            'property_id' => $this->property->id,
            'number' => 'Wohnung EG Rechts',
            'current_tenant_id' => null, // Vacant
        ]);

        // Add ticket on owner's property
        Ticket::factory()->create([
            'property_id' => $this->property->id,
            'title' => 'Dachrinnenreinigung erforderlich',
            'status' => 'in_progress',
        ]);

        // Add invoice for owner
        Invoice::factory()->create([
            'owner_id' => $this->owner->id,
            'invoice_number' => 'ABR-EIGENTUEMER-08',
            'amount' => 3450.00,
            'status' => 'sent',
        ]);

        $response = $this->actingAs($this->ownerUser)->get('/portal/dashboard');

        $response->assertStatus(200);

        // Portfolio & Occupancy
        $response->assertSee('Meine Liegenschaften & Einheiten', false);
        $response->assertSee('Vermietungsquote (50%)');
        $response->assertSee('1 / 2');
        $response->assertSee('Elbterrasse Hamburg');

        // Tickets & Owner Invoices
        $response->assertSee('Dachrinnenreinigung erforderlich');
        $response->assertSee('ABR-EIGENTUEMER-08');
        $response->assertSee('3.450,00 €');

        // Should NOT see tenant lease contract hero card
        $response->assertDontSee('Ihre Mieteinheit');
    }
}
