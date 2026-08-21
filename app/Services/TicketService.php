<?php

namespace App\Services;

use App\DTOs\TicketData;
use App\Events\TicketCreated;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function create(TicketData $data, $user): Ticket
    {
        return DB::transaction(function () use ($data, $user) {
            $ticket = Ticket::create(array_merge($data->toArray(), [
                'created_by' => $user->id,
                'status' => 'open',
            ]));

            event(new TicketCreated($ticket));

            return $ticket;
        });
    }
}
