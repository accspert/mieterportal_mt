<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketStatusUpdatedNotification;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationsAndAuditTest extends TestCase
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
            'name' => 'Admin Manager',
            'email' => 'admin.audit@kundenportal.local',
        ]);
        $this->admin->assignRole('Administrator');

        $this->tenantUser = User::factory()->create([
            'name' => 'Max Mieter',
            'email' => 'max.mieter@kundenportal.local',
        ]);
        $this->tenantUser->assignRole('Mieter');

        $this->tenant = Tenant::factory()->create([
            'user_id' => $this->tenantUser->id,
            'first_name' => 'Max',
            'last_name' => 'Mieter',
        ]);
    }

    public function test_ticket_status_change_dispatches_notification_and_logs_activity(): void
    {
        Notification::fake();

        $ticket = Ticket::factory()->create([
            'title' => 'Heizung defekt',
            'status' => 'open',
            'created_by' => $this->tenantUser->id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Status transition performed by admin
        $this->actingAs($this->admin);
        $ticket->update(['status' => 'in_progress']);

        Notification::assertSentTo(
            $this->tenantUser,
            TicketStatusUpdatedNotification::class,
            function ($notification) {
                return $notification->ticket->status === 'in_progress' && $notification->previousStatus === 'open';
            }
        );

        $activity = Activity::where('subject_type', Ticket::class)
            ->where('subject_id', $ticket->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals($this->admin->id, $activity->causer_id);
    }

    public function test_message_creation_sends_notification_to_recipient(): void
    {
        Notification::fake();

        $message = Message::create([
            'from_user_id' => $this->tenantUser->id,
            'to_user_id' => $this->admin->id,
            'subject' => 'Frage zur Nebenkostenabrechnung',
            'body' => 'Guten Tag, wo finde ich die Belege?',
        ]);

        Notification::assertSentTo(
            $this->admin,
            NewMessageNotification::class,
            function ($notification) use ($message) {
                return $notification->messageModel->id === $message->id;
            }
        );
    }

    public function test_invoice_and_document_mutations_are_recorded_in_activity_log(): void
    {
        $this->actingAs($this->admin);

        // 1. Invoice logging
        $invoice = Invoice::factory()->create([
            'invoice_number' => 'RE-LOG-2026-001',
            'amount' => 450.00,
            'status' => 'sent',
            'tenant_id' => $this->tenant->id,
        ]);

        $invoiceActivity = Activity::where('subject_type', Invoice::class)
            ->where('subject_id', $invoice->id)
            ->first();

        $this->assertNotNull($invoiceActivity);
        $this->assertEquals('created', $invoiceActivity->event);

        // 2. Document logging
        $property = Property::factory()->create();
        $document = Document::create([
            'documentable_type' => Property::class,
            'documentable_id' => $property->id,
            'filename' => 'Energieausweis_2026.pdf',
            'path' => 'documents/energie.pdf',
            'category' => 'certificates',
            'uploaded_by' => $this->admin->id,
        ]);

        $docActivity = Activity::where('subject_type', Document::class)
            ->where('subject_id', $document->id)
            ->first();

        $this->assertNotNull($docActivity);
        $this->assertEquals('created', $docActivity->event);
    }

    public function test_admin_can_view_activity_audit_log_in_filament(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/activities');
        $response->assertStatus(200);
    }

    public function test_all_mail_notifications_are_sent_through_the_queue(): void
    {
        $notificationClasses = collect(glob(app_path('Notifications/*.php')))
            ->map(fn (string $path): string => 'App\\Notifications\\'.basename($path, '.php'));

        $this->assertNotEmpty($notificationClasses);

        foreach ($notificationClasses as $notificationClass) {
            $this->assertTrue(
                is_subclass_of($notificationClass, ShouldQueue::class),
                "{$notificationClass} must implement ShouldQueue so a slow mail server never blocks a request."
            );
        }
    }

    public function test_new_ticket_mail_links_to_the_ticket_in_the_admin_panel(): void
    {
        $ticket = Ticket::factory()->create([
            'created_by' => $this->tenantUser->id,
            'tenant_id' => $this->tenant->id,
        ]);

        $mail = (new TicketCreatedNotification($ticket))->toMail($this->admin);

        $this->assertSame(route('filament.admin.resources.tickets.edit', $ticket), $mail->actionUrl);
        $this->get($mail->actionUrl)->assertRedirect();
    }
}
