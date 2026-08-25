<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InitialSetupSeeder::class);
    }

    public function test_tenant_can_download_their_document(): void
    {
        $property = Property::factory()->create();

        $tenantUser = User::factory()->create();
        $tenantUser->assignRole('Mieter');
        $tenant = Tenant::factory()->create(['user_id' => $tenantUser->id]);
        $unit = Unit::factory()->create(['property_id' => $property->id, 'current_tenant_id' => $tenant->id]);

        $doc = Document::factory()->create([
            'documentable_type' => Tenant::class,
            'documentable_id' => $tenant->id,
            'filename' => 'Mietvertrag_Mustermann.pdf',
            'category' => 'contract',
        ]);

        $response = $this->actingAs($tenantUser)->get(route('portal.documents.download', $doc));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_unauthorized_user_cannot_download_other_tenant_document(): void
    {
        $tenantUser1 = User::factory()->create();
        $tenantUser1->assignRole('Mieter');
        $tenant1 = Tenant::factory()->create(['user_id' => $tenantUser1->id]);

        $tenantUser2 = User::factory()->create();
        $tenantUser2->assignRole('Mieter');
        $tenant2 = Tenant::factory()->create(['user_id' => $tenantUser2->id]);

        $doc1 = Document::factory()->create([
            'documentable_type' => Tenant::class,
            'documentable_id' => $tenant1->id,
            'filename' => 'Geheimer_Mietvertrag.pdf',
        ]);

        $response = $this->actingAs($tenantUser2)->get(route('portal.documents.download', $doc1));
        $response->assertStatus(403);
    }
}
