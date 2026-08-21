<?php

namespace Tests\Feature;

use App\Filament\Resources\MessageResource\Pages\ListMessages;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MessageReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_and_admin_can_reply_to_messages(): void
    {
        $this->seed(InitialSetupSeeder::class);

        $tenant = User::factory()->create(['name' => 'Max Mieter', 'email' => 'tenant@kundenportal.local']);
        $tenant->assignRole('Mieter');

        $staff = User::factory()->create(['name' => 'Sabine Sachbearbeiter', 'email' => 'staff@kundenportal.local']);
        $staff->assignRole('Sachbearbeiter');

        // 1. Tenant sends message
        $message = Message::create([
            'thread_id' => 'thread_101',
            'from_user_id' => $tenant->id,
            'to_user_id' => $staff->id,
            'subject' => 'Frage zu den Nebenkosten',
            'body' => 'Guten Tag, wann kommt die Abrechnung?',
        ]);

        $this->assertNull($message->read_at);

        // 2. Staff replies via Filament MessageResource Action
        Livewire::actingAs($staff)
            ->test(ListMessages::class)
            ->callTableAction('reply', $message, data: [
                'subject' => 'Re: Frage zu den Nebenkosten',
                'body' => 'Guten Tag Herr Mieter, die Abrechnung wird nächste Woche zugestellt.',
            ])
            ->assertHasNoTableActionErrors();

        // 3. Verify reply message was created in database
        $reply = Message::where('from_user_id', $staff->id)
            ->where('to_user_id', $tenant->id)
            ->first();

        $this->assertNotNull($reply);
        $this->assertEquals('Re: Frage zu den Nebenkosten', $reply->subject);
        $this->assertEquals('Guten Tag Herr Mieter, die Abrechnung wird nächste Woche zugestellt.', $reply->body);
        $this->assertEquals('thread_101', $reply->thread_id);

        // 4. Verify original message was marked as read
        $this->assertNotNull($message->fresh()->read_at);

        // 5. Verify tenant sees the reply in the customer portal
        $response = $this->actingAs($tenant)->get(route('portal.messages.index'));
        $response->assertStatus(200);
        $response->assertSee('Guten Tag Herr Mieter, die Abrechnung wird nächste Woche zugestellt.');
    }

    public function test_staff_can_view_message_details(): void
    {
        $this->seed(InitialSetupSeeder::class);

        $tenant = User::factory()->create(['name' => 'Max Mieter', 'email' => 'tenant@kundenportal.local']);
        $tenant->assignRole('Mieter');

        $staff = User::factory()->create(['name' => 'Sabine Sachbearbeiter', 'email' => 'staff@kundenportal.local']);
        $staff->assignRole('Sachbearbeiter');

        $message = Message::create([
            'from_user_id' => $tenant->id,
            'to_user_id' => $staff->id,
            'subject' => 'Wasserschaden Bad',
            'body' => 'Es tropft von der Decke.',
        ]);

        Livewire::actingAs($staff)
            ->test(ListMessages::class)
            ->callTableAction('view', $message)
            ->assertHasNoTableActionErrors();
    }
}
