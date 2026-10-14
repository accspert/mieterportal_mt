<?php

namespace Tests\Feature;

use App\Models\CompanyTenant;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use App\Services\TenantOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TenantOnboardingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    protected function onboardingPayload(string $slug, string $adminEmail = 'hans@bern-immo.ch'): array
    {
        return [
            'company_name' => 'Immobilien Zentrum Bern',
            'slug' => $slug,
            'admin_name' => 'Hans Meier',
            'admin_email' => $adminEmail,
            'phone' => '+41 31 987 65 43',
            'plan' => 'standard',
        ];
    }

    public function test_the_service_creates_the_property_management_and_invites_its_administrator(): void
    {
        Notification::fake();

        $tenant = app(TenantOnboardingService::class)->createTenant([
            'company_name' => 'Alpen Real Estate AG',
            'slug' => 'alpen-immo',
            'admin_name' => 'Max Muster',
            'admin_email' => 'Admin@Alpen-Immo.ch',
            'plan' => 'standard',
        ]);

        $this->assertSame('alpen-immo', $tenant->id);
        $this->assertDatabaseHas('company_tenants', ['id' => 'alpen-immo', 'company_name' => 'Alpen Real Estate AG', 'plan' => 'standard']);

        $admin = User::withoutGlobalScopes()->where('email', 'admin@alpen-immo.ch')->firstOrFail();

        $this->assertSame('alpen-immo', $admin->company_tenant_id);
        $this->assertTrue($admin->hasRole('Administrator'));

        Notification::assertSentTo($admin, UserInvitationNotification::class, function (UserInvitationNotification $notification) use ($admin): bool {
            $url = $notification->toMail($admin)->actionUrl;

            return str_contains($url, '/portal/password/reset/') && str_contains($url, 'invitation=1');
        });
    }

    public function test_the_api_creates_a_property_management_with_the_onboarding_token(): void
    {
        Notification::fake();
        config(['tenancy.onboarding_token' => 'geheimes-onboarding-token']);

        $this->withToken('geheimes-onboarding-token')
            ->postJson(route('tenants.onboard'), $this->onboardingPayload('bern-immo'))
            ->assertCreated()
            ->assertJsonPath('tenant_id', 'bern-immo');

        $this->assertDatabaseHas('company_tenants', ['id' => 'bern-immo']);
        $this->assertDatabaseHas('users', ['email' => 'hans@bern-immo.ch', 'company_tenant_id' => 'bern-immo']);
    }

    public function test_an_e_mail_address_that_already_has_an_account_is_rejected(): void
    {
        config(['tenancy.onboarding_token' => 'geheimes-onboarding-token']);
        User::factory()->create(['email' => 'hans@bern-immo.ch']);

        $this->withToken('geheimes-onboarding-token')
            ->postJson(route('tenants.onboard'), $this->onboardingPayload('bern-immo'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('admin_email');

        $this->assertDatabaseMissing('company_tenants', ['id' => 'bern-immo']);
    }

    public function test_onboarding_is_rejected_without_valid_token(): void
    {
        config(['tenancy.onboarding_token' => 'geheimes-onboarding-token']);

        $this->postJson(route('tenants.onboard'), $this->onboardingPayload('ohne-token'))->assertForbidden();
        $this->withToken('falsches-token')->postJson(route('tenants.onboard'), $this->onboardingPayload('falsches-token'))->assertForbidden();

        $this->assertDatabaseMissing('company_tenants', ['id' => 'ohne-token']);
        $this->assertDatabaseMissing('company_tenants', ['id' => 'falsches-token']);
    }

    public function test_onboarding_is_disabled_when_no_token_is_configured(): void
    {
        config(['tenancy.onboarding_token' => null]);

        $this->withToken('')
            ->postJson(route('tenants.onboard'), $this->onboardingPayload('kein-token-konfiguriert'))
            ->assertForbidden();

        $this->assertDatabaseMissing('company_tenants', ['id' => 'kein-token-konfiguriert']);
    }

    public function test_onboarding_rejects_reserved_and_non_normalized_identifiers(): void
    {
        config(['tenancy.onboarding_token' => 'geheimes-onboarding-token']);

        foreach (['admin', 'Gross-Buchstaben', 'unter_strich', '-bindestrich', 'test'] as $slug) {
            $this->withToken('geheimes-onboarding-token')
                ->postJson(route('tenants.onboard'), $this->onboardingPayload($slug))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('slug');
        }

        $this->assertSame(1, CompanyTenant::count(), 'Only the property management of the test case exists.');
    }

    public function test_onboarding_endpoint_is_rate_limited(): void
    {
        config(['tenancy.onboarding_token' => 'geheimes-onboarding-token']);

        foreach (range(1, 5) as $attempt) {
            $this->postJson(route('tenants.onboard'), [])->assertForbidden();
        }

        $this->postJson(route('tenants.onboard'), [])->assertTooManyRequests();
    }
}
