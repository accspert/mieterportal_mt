<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantUserCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\InitialSetupSeeder::class);
    }

    public function test_staff_can_create_user_account_for_tenant(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('Sachbearbeiter');

        $tenant = Tenant::factory()->create([
            'first_name' => 'Klaus',
            'last_name' => 'Klausen',
            'email' => 'klaus.klausen@example.com',
            'user_id' => null,
        ]);

        $this->assertNull($tenant->user_id);

        // Simulate creation action logic
        $plainPassword = 'TestPassword123!';
        $user = User::create([
            'name' => "{$tenant->first_name} {$tenant->last_name}",
            'email' => $tenant->email,
            'password' => Hash::make($plainPassword),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Mieter');
        $tenant->update(['user_id' => $user->id]);

        $tenant->refresh();
        $this->assertNotNull($tenant->user_id);
        $this->assertEquals($user->id, $tenant->user_id);
        $this->assertTrue($user->hasRole('Mieter'));
        $this->assertTrue(Hash::check($plainPassword, $user->password));

        // The tenant can now log into the portal
        $response = $this->post(route('portal.login.post'), [
            'email' => 'klaus.klausen@example.com',
            'password' => $plainPassword,
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_tenant_account_created_with_specific_manager_routes_tickets_to_that_manager(): void
    {
        $ahmad = User::create([
            'name' => 'Ahmad Sahid',
            'email' => 'ahmad.sahid@kundenportal.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $ahmad->assignRole('Sachbearbeiter');

        $tenant = Tenant::factory()->create([
            'first_name' => 'Sara',
            'last_name' => 'Schmidt',
            'email' => 'sara.schmidt@example.com',
            'user_id' => null,
            'manager_id' => null,
        ]);

        // Creating portal account for tenant and assigning Ahmad Sahid as manager
        $user = User::create([
            'name' => "{$tenant->first_name} {$tenant->last_name}",
            'email' => $tenant->email,
            'password' => Hash::make('Secret1234'),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('Mieter');
        $tenant->update([
            'user_id' => $user->id,
            'manager_id' => $ahmad->id,
        ]);

        $this->assertEquals($ahmad->id, $tenant->fresh()->manager_id);

        // Tenant creates a damage report
        $this->actingAs($user)->post(route('portal.tickets.store'), [
            'title' => 'Licht im Hausflur defekt',
            'description' => 'Die Deckenlampe vor der Wohnungstür funktioniert nicht mehr.',
            'category' => 'repair',
            'priority' => 'medium',
        ]);

        $ticket = \App\Models\Ticket::where('title', 'Licht im Hausflur defekt')->first();
        $this->assertNotNull($ticket);
        $this->assertEquals($ahmad->id, $ticket->assigned_to);
    }
}
