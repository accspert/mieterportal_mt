<?php

namespace Tests\Feature;

use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InitialSetupSeeder::class);
    }

    public function test_application_defaults_to_german(): void
    {
        $response = $this->get('/portal/login');
        $response->assertStatus(200);
        $this->assertEquals('de', App::getLocale());
    }

    public function test_user_can_switch_locale_to_english(): void
    {
        $response = $this->get(route('locale.switch', 'en'));
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');

        // Subsequent request uses English
        $portalResponse = $this->withSession(['locale' => 'en'])->get('/portal/login');
        $portalResponse->assertStatus(200);
        $portalResponse->assertSee('Customer Portal');
        $this->assertEquals('en', App::getLocale());
    }

    public function test_user_can_switch_locale_back_to_german(): void
    {
        $response = $this->withSession(['locale' => 'en'])->get(route('locale.switch', 'de'));
        $response->assertRedirect();
        $response->assertSessionHas('locale', 'de');

        $portalResponse = $this->withSession(['locale' => 'de'])->get('/portal/login');
        $portalResponse->assertStatus(200);
        $portalResponse->assertSee('Kundenportal');
        $this->assertEquals('de', App::getLocale());
    }

    public function test_invalid_locale_falls_back_to_german(): void
    {
        $response = $this->get(route('locale.switch', 'invalid_lang'));
        $response->assertRedirect();

        $portalResponse = $this->withSession(['locale' => 'invalid_lang'])->get('/portal/login');
        $portalResponse->assertStatus(200);
        $this->assertEquals('de', App::getLocale());
    }
}
