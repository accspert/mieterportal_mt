<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementNoticeBoardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tenantUser;

    protected Tenant $tenant;

    protected Property $propertyA;

    protected Property $propertyB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin.notice@kundenportal.local',
        ]);
        $this->admin->assignRole('Administrator');

        $this->tenantUser = User::factory()->create([
            'name' => 'Clara Mieterin',
            'email' => 'clara.notice@kundenportal.local',
        ]);
        $this->tenantUser->assignRole('Mieter');

        $this->tenant = Tenant::factory()->create([
            'user_id' => $this->tenantUser->id,
            'first_name' => 'Clara',
            'last_name' => 'Mieterin',
            'email' => 'clara.notice@kundenportal.local',
        ]);

        $this->propertyA = Property::factory()->create(['name' => 'Residenz Alster']);
        $this->propertyB = Property::factory()->create(['name' => 'Parkvilla Elbe']);

        $unit = Unit::factory()->create([
            'property_id' => $this->propertyA->id,
            'current_tenant_id' => $this->tenant->id,
            'number' => 'Wohnung 3B',
        ]);
    }

    public function test_tenant_sees_global_and_own_property_announcements(): void
    {
        // Global announcement
        Announcement::factory()->create([
            'title' => 'Wartung Heizungsanlage Global',
            'body' => 'Am Dienstag finden Messungen in allen Objekten statt.',
            'type' => 'warning',
            'property_id' => null,
            'is_active' => true,
        ]);

        // Announcement for Property A (Tenant's building)
        Announcement::factory()->create([
            'title' => 'Dachreinigung Residenz Alster',
            'body' => 'Fassaden- und Dacharbeiten im Innenhof.',
            'type' => 'info',
            'property_id' => $this->propertyA->id,
            'is_active' => true,
        ]);

        // Announcement for Property B (Other building)
        Announcement::factory()->create([
            'title' => 'Aufzugsausfall Parkvilla Elbe',
            'body' => 'Nur fuer Parkvilla Elbe Bewohner relevant.',
            'type' => 'urgent',
            'property_id' => $this->propertyB->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->tenantUser)->get('/portal/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Wartung Heizungsanlage Global');
        $response->assertSee('Dachreinigung Residenz Alster');
        $response->assertDontSee('Aufzugsausfall Parkvilla Elbe');
    }

    public function test_inactive_and_expired_announcements_are_hidden(): void
    {
        // Inactive
        Announcement::factory()->create([
            'title' => 'Inaktiver Test-Aushang',
            'is_active' => false,
        ]);

        // Expired
        Announcement::factory()->create([
            'title' => 'Abgelaufener Aushang',
            'is_active' => true,
            'ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->tenantUser)->get('/portal/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee('Inaktiver Test-Aushang');
        $response->assertDontSee('Abgelaufener Aushang');
    }

    public function test_admin_can_crud_announcements_via_api(): void
    {
        $token = $this->admin->createToken('test-token')->plainTextToken;

        // Create
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/announcements', [
                'title' => 'Notfall Reparatur Wasserrohr',
                'body' => 'Hauptwasseranschluss wird fuer 2 Stunden abgestellt.',
                'type' => 'urgent',
                'property_id' => $this->propertyA->id,
                'starts_at' => now()->toISOString(),
                'ends_at' => now()->addDays(2)->toISOString(),
                'is_active' => true,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.title', 'Notfall Reparatur Wasserrohr');
        $announcementId = $response->json('data.id');

        // Show
        $showResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/announcements/{$announcementId}");
        $showResponse->assertStatus(200);
        $showResponse->assertJsonPath('data.type', 'urgent');

        // Update
        $updateResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/announcements/{$announcementId}", [
                'type' => 'warning',
            ]);
        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('data.type', 'warning');

        // Delete
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/announcements/{$announcementId}");
        $deleteResponse->assertStatus(204);

        $this->assertSoftDeleted('announcements', ['id' => $announcementId]);
    }
}
