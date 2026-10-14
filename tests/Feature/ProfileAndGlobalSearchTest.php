<?php

namespace Tests\Feature;

use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PropertyResource;
use App\Filament\Resources\RenterResource;
use App\Filament\Resources\TicketResource;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileAndGlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tenantUser;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);

        $this->admin = User::factory()->create([
            'name' => 'Admin Global',
            'email' => 'admin.search@kundenportal.local',
        ]);
        $this->admin->assignRole('Administrator');

        $this->tenantUser = User::factory()->create([
            'name' => 'Felix Mieter',
            'email' => 'felix.profile@kundenportal.local',
            'password' => Hash::make('old-secret-123'),
        ]);
        $this->tenantUser->assignRole('Mieter');

        $this->tenant = Tenant::factory()->create([
            'user_id' => $this->tenantUser->id,
            'first_name' => 'Felix',
            'last_name' => 'Mieter',
            'email' => 'felix.profile@kundenportal.local',
            'phone' => '+49 171 1112233',
        ]);
    }

    public function test_tenant_can_view_profile_page(): void
    {
        $response = $this->actingAs($this->tenantUser)->get('/portal/profile');

        $response->assertStatus(200);
        $response->assertSee('Felix Mieter');
        $response->assertSee('+49 171 1112233');
    }

    public function test_tenant_can_update_contact_details(): void
    {
        $response = $this->actingAs($this->tenantUser)->put('/portal/profile/contact', [
            'first_name' => 'Felix',
            'last_name' => 'Neuhaus',
            'email' => 'felix.neu@kundenportal.local',
            'phone' => '+49 172 9998877',
        ]);

        $response->assertRedirect(route('portal.profile.show'));

        $this->tenantUser->refresh();
        $this->tenant->refresh();

        $this->assertEquals('Felix Neuhaus', $this->tenantUser->name);
        $this->assertEquals('felix.neu@kundenportal.local', $this->tenantUser->email);
        $this->assertEquals('+49 172 9998877', $this->tenant->phone);
    }

    public function test_tenant_can_update_password_with_valid_current_password(): void
    {
        $response = $this->actingAs($this->tenantUser)->put('/portal/profile/password', [
            'current_password' => 'old-secret-123',
            'password' => 'new-secret-password-456',
            'password_confirmation' => 'new-secret-password-456',
        ]);

        $response->assertRedirect(route('portal.profile.show'));

        $this->tenantUser->refresh();
        $this->assertTrue(Hash::check('new-secret-password-456', $this->tenantUser->password));
    }

    public function test_password_update_fails_with_invalid_current_password(): void
    {
        $response = $this->actingAs($this->tenantUser)->put('/portal/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-secret-password-456',
            'password_confirmation' => 'new-secret-password-456',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_filament_resources_define_global_search_attributes(): void
    {
        $property = Property::factory()->create(['name' => 'Alsterresidenz Hamburg']);
        $ticket = Ticket::factory()->create(['title' => 'Dachschaden Unwetter']);
        $invoice = Invoice::factory()->create(['invoice_number' => 'RE-GLOBAL-999']);

        $this->assertEquals(['first_name', 'last_name', 'email', 'phone'], RenterResource::getGloballySearchableAttributes());
        $this->assertEquals(['name', 'street', 'city', 'postal_code'], PropertyResource::getGloballySearchableAttributes());
        $this->assertEquals(['title', 'description'], TicketResource::getGloballySearchableAttributes());
        $this->assertEquals(['invoice_number', 'notes'], InvoiceResource::getGloballySearchableAttributes());

        $this->assertStringContainsString('Alsterresidenz Hamburg', PropertyResource::getGlobalSearchResultTitle($property));
        $this->assertStringContainsString('Dachschaden Unwetter', TicketResource::getGlobalSearchResultTitle($ticket));
        $this->assertStringContainsString('RE-GLOBAL-999', InvoiceResource::getGlobalSearchResultTitle($invoice));
    }
}
