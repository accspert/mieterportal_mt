<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTicketIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InitialSetupSeeder::class);
    }

    public function test_tenant_only_sees_their_own_tickets_in_portal(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('Sachbearbeiter');

        $property = Property::factory()->create(['manager_id' => $staff->id]);

        $tenantUser1 = User::factory()->create();
        $tenantUser1->assignRole('Mieter');
        $tenant1 = Tenant::factory()->create(['user_id' => $tenantUser1->id]);
        $unit1 = Unit::factory()->create(['property_id' => $property->id, 'current_tenant_id' => $tenant1->id]);

        $tenantUser2 = User::factory()->create();
        $tenantUser2->assignRole('Mieter');
        $tenant2 = Tenant::factory()->create(['user_id' => $tenantUser2->id]);
        $unit2 = Unit::factory()->create(['property_id' => $property->id, 'current_tenant_id' => $tenant2->id]);

        $ticket1 = Ticket::factory()->create([
            'title' => 'Heizung kaputt Mieter 1',
            'tenant_id' => $tenant1->id,
            'unit_id' => $unit1->id,
            'property_id' => $property->id,
            'created_by' => $tenantUser1->id,
            'assigned_to' => $staff->id,
        ]);

        $ticket2 = Ticket::factory()->create([
            'title' => 'Wasserhahn tropft Mieter 2',
            'tenant_id' => $tenant2->id,
            'unit_id' => $unit2->id,
            'property_id' => $property->id,
            'created_by' => $tenantUser2->id,
            'assigned_to' => $staff->id,
        ]);

        // Tenant 1 checks tickets index
        $response1 = $this->actingAs($tenantUser1)->get(route('portal.tickets.index'));
        $response1->assertStatus(200);
        $response1->assertSee('Heizung kaputt Mieter 1');
        $response1->assertDontSee('Wasserhahn tropft Mieter 2');

        // Tenant 1 tries to access Ticket 2 directly -> 403
        $forbiddenResponse = $this->actingAs($tenantUser1)->get(route('portal.tickets.show', $ticket2));
        $forbiddenResponse->assertStatus(403);

        // Tenant 1 accesses Ticket 1 -> 200
        $allowedResponse = $this->actingAs($tenantUser1)->get(route('portal.tickets.show', $ticket1));
        $allowedResponse->assertStatus(200);
        $allowedResponse->assertSee('Heizung kaputt Mieter 1');
    }

    public function test_tenant_creating_ticket_automatically_assigns_unit_property_and_manager(): void
    {
        $staff = User::factory()->create(['name' => 'Herr Mueller Sachbearbeiter']);
        $staff->assignRole('Sachbearbeiter');

        $property = Property::factory()->create([
            'name' => 'Wohnpark Sonnenschein',
            'manager_id' => $staff->id,
        ]);

        $tenantUser = User::factory()->create(['name' => 'Anna Mieterin']);
        $tenantUser->assignRole('Mieter');
        $tenant = Tenant::factory()->create([
            'user_id' => $tenantUser->id,
            'first_name' => 'Anna',
            'last_name' => 'Mieterin',
        ]);

        $unit = Unit::factory()->create([
            'property_id' => $property->id,
            'number' => '4B',
            'current_tenant_id' => $tenant->id,
        ]);

        $response = $this->actingAs($tenantUser)->post(route('portal.tickets.store'), [
            'title' => 'Fenster schließt nicht richtig',
            'description' => 'Das Fenster im Schlafzimmer lässt sich nicht mehr verriegeln.',
            'category' => 'repair',
            'priority' => 'high',
            'unit_id' => $unit->id,
        ]);

        $response->assertRedirect();

        $ticket = Ticket::where('title', 'Fenster schließt nicht richtig')->first();
        $this->assertNotNull($ticket);
        $this->assertEquals($property->id, $ticket->property_id);
        $this->assertEquals($unit->id, $ticket->unit_id);
        $this->assertEquals($tenant->id, $ticket->tenant_id);
        $this->assertEquals($tenantUser->id, $ticket->created_by);
        $this->assertEquals($staff->id, $ticket->assigned_to);
    }

    public function test_tenant_cannot_create_ticket_for_foreign_unit_or_property(): void
    {
        $ownProperty = Property::factory()->create();
        $foreignProperty = Property::factory()->create();

        $renterUser = User::factory()->create();
        $renterUser->assignRole('Mieter');
        $renter = Renter::factory()->create(['user_id' => $renterUser->id]);
        $ownUnit = Unit::factory()->create(['property_id' => $ownProperty->id, 'current_renter_id' => $renter->id]);

        $foreignUnit = Unit::factory()->create(['property_id' => $foreignProperty->id]);

        $payload = [
            'title' => 'Fremde Meldung',
            'description' => 'Ticket auf fremde Wohnung.',
            'category' => 'repair',
            'priority' => 'low',
        ];

        $this->actingAs($renterUser)
            ->post(route('portal.tickets.store'), $payload + ['unit_id' => $foreignUnit->id])
            ->assertSessionHasErrors('unit_id');

        $this->actingAs($renterUser)
            ->post(route('portal.tickets.store'), $payload + ['property_id' => $foreignProperty->id])
            ->assertSessionHasErrors('property_id');

        $this->assertDatabaseMissing('tickets', ['title' => 'Fremde Meldung']);

        $this->actingAs($renterUser)
            ->post(route('portal.tickets.store'), ['title' => 'Eigene Meldung'] + $payload + ['unit_id' => $ownUnit->id, 'property_id' => $ownProperty->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tickets', ['title' => 'Eigene Meldung', 'unit_id' => $ownUnit->id]);
    }

    public function test_owner_can_create_ticket_only_for_own_properties(): void
    {
        $ownerUser = User::factory()->create();
        $ownerUser->assignRole('Eigentümer');
        $owner = Owner::factory()->create(['user_id' => $ownerUser->id]);

        $ownProperty = Property::factory()->create(['owner_id' => $owner->id]);
        $ownUnit = Unit::factory()->create(['property_id' => $ownProperty->id]);
        $foreignProperty = Property::factory()->create();

        $payload = [
            'description' => 'Meldung eines Eigentümers.',
            'category' => 'repair',
            'priority' => 'low',
        ];

        $this->actingAs($ownerUser)
            ->post(route('portal.tickets.store'), ['title' => 'Fremdes Objekt', 'property_id' => $foreignProperty->id] + $payload)
            ->assertSessionHasErrors('property_id');

        $this->actingAs($ownerUser)
            ->post(route('portal.tickets.store'), ['title' => 'Eigenes Objekt', 'property_id' => $ownProperty->id, 'unit_id' => $ownUnit->id] + $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('tickets', ['title' => 'Fremdes Objekt']);
        $this->assertDatabaseHas('tickets', ['title' => 'Eigenes Objekt', 'property_id' => $ownProperty->id]);
    }
}
