<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\TicketStatusUpdatedNotification;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWidgetsAndNotificationsTest extends TestCase
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
            'name' => 'Admin Boss',
            'email' => 'admin.dash@kundenportal.local',
        ]);
        $this->admin->assignRole('Administrator');

        $this->tenantUser = User::factory()->create([
            'name' => 'Laura Mieterin',
            'email' => 'laura@kundenportal.local',
        ]);
        $this->tenantUser->assignRole('Mieter');

        $this->tenant = Tenant::factory()->create([
            'user_id' => $this->tenantUser->id,
            'first_name' => 'Laura',
            'last_name' => 'Mieterin',
        ]);

        $property = Property::factory()->create();
        $unit = Unit::factory()->create([
            'property_id' => $property->id,
            'current_tenant_id' => $this->tenant->id,
        ]);

        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $unit->id,
            'amount' => 650.00,
            'status' => 'sent',
        ]);

        Ticket::factory()->create([
            'title' => 'Klingel defekt',
            'status' => 'open',
            'created_by' => $this->tenantUser->id,
        ]);
    }

    public function test_admin_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertStatus(200);
    }

    public function test_tenant_can_mark_notifications_as_read(): void
    {
        $this->tenantUser->unreadNotifications()->delete();
        $ticket = Ticket::first();
        $this->tenantUser->notify(new TicketStatusUpdatedNotification($ticket, 'open'));

        $this->assertEquals(1, $this->tenantUser->unreadNotifications()->count());

        $response = $this->actingAs($this->tenantUser)
            ->post('/portal/notifications/mark-read');

        $response->assertStatus(302);
        $this->assertEquals(0, $this->tenantUser->fresh()->unreadNotifications()->count());
    }
}
