<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Property;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tenantUser;

    protected string $adminToken;

    protected string $tenantToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin.api@kundenportal.local',
        ]);
        $this->admin->assignRole('Administrator');
        $this->adminToken = $this->admin->createToken('admin-api')->plainTextToken;

        $this->tenantUser = User::factory()->create([
            'email' => 'tenant.api@kundenportal.local',
        ]);
        $this->tenantUser->assignRole('Mieter');
        $this->tenantToken = $this->tenantUser->createToken('tenant-api')->plainTextToken;
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/invoices')->assertStatus(401);
        $this->getJson('/api/tasks')->assertStatus(401);
        $this->getJson('/api/documents')->assertStatus(401);
        $this->getJson('/api/messages')->assertStatus(401);
    }

    public function test_admin_can_manage_invoices_via_api(): void
    {
        // 1. Create invoice
        $createResponse = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->postJson('/api/invoices', [
                'invoice_number' => 'INV-API-2026-001',
                'amount' => 1250.00,
                'due_date' => '2026-10-01',
                'status' => 'sent',
                'notes' => 'API created invoice',
            ]);

        $createResponse->assertStatus(201);
        $invoiceId = $createResponse->json('data.id');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'invoice_number' => 'INV-API-2026-001',
            'amount' => 1250.00,
        ]);

        // 2. Show invoice
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->getJson('/api/invoices/'.$invoiceId)
            ->assertStatus(200)
            ->assertJsonPath('data.invoice_number', 'INV-API-2026-001');

        // 3. Update invoice
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->putJson('/api/invoices/'.$invoiceId, [
                'status' => 'paid',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'paid');

        // 4. List invoices
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->getJson('/api/invoices?status=paid')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // 5. Delete invoice
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->deleteJson('/api/invoices/'.$invoiceId)
            ->assertStatus(204);

        $this->assertSoftDeleted('invoices', ['id' => $invoiceId]);
    }

    public function test_admin_can_manage_tasks_via_api(): void
    {
        // 1. Create task
        $createResponse = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->postJson('/api/tasks', [
                'title' => 'Heizungsprüfung durchführen',
                'description' => 'Jährliche Wartung der Heizungsanlage',
                'priority' => 'high',
                'status' => 'pending',
                'due_at' => now()->addDays(7)->toISOString(),
            ]);

        $createResponse->assertStatus(201);
        $taskId = $createResponse->json('data.id');

        // 2. Show task
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->getJson('/api/tasks/'.$taskId)
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Heizungsprüfung durchführen');

        // 3. Update task
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->putJson('/api/tasks/'.$taskId, [
                'status' => 'in_progress',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'in_progress');

        // 4. Delete task
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->deleteJson('/api/tasks/'.$taskId)
            ->assertStatus(204);

        $this->assertSoftDeleted('tasks', ['id' => $taskId]);
    }

    public function test_admin_can_manage_documents_via_api(): void
    {
        $property = Property::factory()->create();

        // 1. Create document
        $createResponse = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->postJson('/api/documents', [
                'documentable_type' => Property::class,
                'documentable_id' => $property->id,
                'filename' => 'Gebaeudeplan_2026.pdf',
                'path' => 'documents/Gebaeudeplan_2026.pdf',
                'category' => 'general',
                'version' => 1,
            ]);

        $createResponse->assertStatus(201);
        $docId = $createResponse->json('data.id');

        // 2. List documents
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->getJson('/api/documents?category=general')
            ->assertStatus(200)
            ->assertJsonPath('data.0.filename', 'Gebaeudeplan_2026.pdf');

        // 3. Delete document
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->deleteJson('/api/documents/'.$docId)
            ->assertStatus(204);

        $this->assertSoftDeleted('documents', ['id' => $docId]);
    }

    public function test_messages_api_send_and_mark_as_read(): void
    {
        // 1. Tenant sends message
        $sendResponse = $this->withHeader('Authorization', 'Bearer '.$this->tenantToken)
            ->postJson('/api/messages', [
                'to_user_id' => $this->admin->id,
                'subject' => 'Anfrage Stellplatz',
                'body' => 'Ist ab nächsten Monat ein Tiefgaragenstellplatz frei?',
            ]);

        $sendResponse->assertStatus(201);
        $messageId = $sendResponse->json('data.id');

        // 2. Admin receives and lists message
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->getJson('/api/messages')
            ->assertStatus(200)
            ->assertJsonPath('data.0.subject', 'Anfrage Stellplatz');

        // 3. Admin marks message as read
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->patchJson('/api/messages/'.$messageId.'/read')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $messageId);

        $this->assertNotNull(Message::find($messageId)->read_at);
    }
}
