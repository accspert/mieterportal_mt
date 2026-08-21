<?php

use App\DTOs\TicketData;
use App\Jobs\SendTicketNotificationJob;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Ensure role exists
    Role::firstOrCreate(['name' => 'Sachbearbeiter']);
});

it('creates a ticket and dispatches notification job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole('Sachbearbeiter');

    $data = TicketData::fromArray([
        'title' => 'Test Ticket',
        'description' => 'Issue description',
        'priority' => 'high',
    ]);

    $service = app(TicketService::class);
    $ticket = $service->create($data, $user);

    expect($ticket)->not->toBeNull();
    expect($ticket->title)->toBe('Test Ticket');

    Queue::assertPushed(SendTicketNotificationJob::class, function ($job) use ($ticket) {
        return $job->ticket->id === $ticket->id;
    });
});
