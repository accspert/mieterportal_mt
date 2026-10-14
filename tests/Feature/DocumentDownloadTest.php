<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
        $unit = Unit::factory()->create(['property_id' => $property->id, 'current_renter_id' => $tenant->id]);

        $doc = Document::factory()->create([
            'documentable_type' => Tenant::class,
            'documentable_id' => $tenant->id,
            'filename' => 'Mietvertrag_Mustermann.pdf',
            'category' => 'contract',
        ]);

        $response = $this->actingAs($tenantUser)->get("/portal/documents/{$doc->id}/download");

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

        $response = $this->actingAs($tenantUser2)->get("/portal/documents/{$doc1->id}/download");
        $response->assertStatus(403);
    }

    public function test_tenant_can_download_jpeg_image_with_correct_extension_and_mime(): void
    {
        Storage::fake('public');
        $filePath = 'documents/schadensfoto.jpg';
        Storage::disk('public')->put($filePath, 'fake-jpeg-binary-content');

        $tenantUser = User::factory()->create();
        $tenantUser->assignRole('Mieter');
        $renter = Renter::factory()->create(['user_id' => $tenantUser->id]);

        $doc = Document::factory()->create([
            'documentable_type' => Renter::class,
            'documentable_id' => $renter->id,
            'filename' => 'Schadensfoto_Wohnzimmer',
            'path' => $filePath,
            'mime_type' => 'image/jpeg',
            'category' => 'general',
        ]);

        $this->assertTrue($doc->is_image);
        $this->assertEquals('jpg', $doc->file_extension);
        $this->assertEquals('JPG', $doc->file_type_badge['label']);
        $this->assertEquals('Schadensfoto_Wohnzimmer.jpg', $doc->download_filename);

        $response = $this->actingAs($tenantUser)->get("/portal/documents/{$doc->id}/download");

        $response->assertStatus(200);
        $response->assertDownload('Schadensfoto_Wohnzimmer.jpg');
    }

    public function test_user_cannot_take_over_renter_documents_by_changing_email_to_renter_email(): void
    {
        $victim = Renter::factory()->create(['user_id' => null, 'email' => 'opfer@example.com']);
        $victimDocument = Document::factory()->create([
            'documentable_type' => Renter::class,
            'documentable_id' => $victim->id,
            'filename' => 'Mietvertrag_Opfer.pdf',
            'category' => 'contract',
            'uploaded_by' => null,
        ]);

        $attacker = User::factory()->create(['email' => 'angreifer@example.com']);
        $attacker->assignRole('Eigentümer');
        Owner::factory()->create(['user_id' => $attacker->id]);

        $this->actingAs($attacker)->put('/portal/profile/contact', [
            'first_name' => 'Eve',
            'last_name' => 'Angreifer',
            'email' => 'opfer@example.com',
        ])->assertSessionHasNoErrors();

        $this->assertNull($attacker->fresh()->resolvedRenter());

        $this->actingAs($attacker->fresh())
            ->get("/portal/documents/{$victimDocument->id}/download")
            ->assertForbidden();

        $this->actingAs($attacker->fresh())
            ->get('/portal/documents')
            ->assertOk()
            ->assertDontSee('Mietvertrag_Opfer.pdf');
    }
}
