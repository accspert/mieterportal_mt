<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InitialSetupSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('Mieter');
    }

    public function test_application_defaults_to_german(): void
    {
        $this->actingAs($this->user)->get('/portal/profile')->assertOk()->assertSee('Abmelden');
        $this->assertEquals('de', App::getLocale());
    }

    public function test_user_can_switch_locale_to_english(): void
    {
        $response = $this->get(route('locale.switch', 'en'));
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');

        // Subsequent request uses English
        $portalResponse = $this->actingAs($this->user)->withSession(['locale' => 'en'])->get('/portal/profile');
        $portalResponse->assertStatus(200);
        $portalResponse->assertSee('Log Out');
        $this->assertEquals('en', App::getLocale());
    }

    public function test_user_can_switch_locale_back_to_german(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get(route('locale.switch', 'de'));
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'de');

        $portalResponse = $this->actingAs($this->user)->withSession(['locale' => 'de'])->get('/portal/profile');
        $portalResponse->assertStatus(200);
        $portalResponse->assertSee('Abmelden');
        $this->assertEquals('de', App::getLocale());
    }

    public function test_invalid_locale_falls_back_to_german(): void
    {
        $response = $this->get(route('locale.switch', 'invalid_lang'));
        $response->assertRedirect();

        $portalResponse = $this->actingAs($this->user)->withSession(['locale' => 'invalid_lang'])->get('/portal/profile');
        $portalResponse->assertStatus(200);
        $this->assertEquals('de', App::getLocale());
    }
}
