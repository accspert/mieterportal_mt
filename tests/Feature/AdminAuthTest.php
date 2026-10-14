<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CentralLoginService;
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

    public function test_guests_of_the_admin_panel_are_sent_to_the_central_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get(route('login'))->assertRedirect(CentralLoginService::loginUrl());
    }

    public function test_admin_logout_from_filament_redirects_to_central_login(): void
    {
        $this->seed(InitialSetupSeeder::class);

        $user = User::factory()->create([
            'email' => 'admin@kundenportal.local',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('Administrator');

        $response = $this->actingAs($user)->post('/admin/logout');

        $response->assertRedirect(CentralLoginService::loginUrl(['abgemeldet' => 1]));
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
            ->get('/admin/renters')
            ->assertStatus(200);
    }
}
