<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_accessing_admin_is_redirected_to_portal_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
    }

    public function test_admin_user_authenticates_via_portal_login_and_redirects_to_admin(): void
    {
        $this->seed(InitialSetupSeeder::class);

        $user = User::factory()->create([
            'email' => 'admin@kundenportal.local',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('Administrator');

        $response = $this->post('/portal/login', [
            'email' => 'admin@kundenportal.local',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_logout_from_filament_redirects_to_portal_login(): void
    {
        $this->seed(InitialSetupSeeder::class);

        $user = User::factory()->create([
            'email' => 'admin@kundenportal.local',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('Administrator');

        $response = $this->actingAs($user)->post('/admin/logout');

        $response->assertRedirect('/portal/login');
        $this->assertGuest();
    }

    public function test_admin_can_access_dashboard_and_resources(): void
    {
        $this->seed(InitialSetupSeeder::class);

        $user = User::factory()->create([
            'email' => 'admin@kundenportal.local',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('Administrator');

        $this->actingAs($user)
            ->get('/admin')
            ->assertStatus(200);

        $this->actingAs($user)
            ->get('/admin/properties')
            ->assertStatus(200);

        $this->actingAs($user)
            ->get('/admin/units')
            ->assertStatus(200);

        $this->actingAs($user)
            ->get('/admin/tenants')
            ->assertStatus(200);
    }
}
