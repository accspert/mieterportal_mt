<?php

namespace App\Services;

use App\DTOs\TicketData;
use App\Events\TicketCreated;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TicketService
{
    /**
     * Create a ticket from validated API data via TicketData DTO.
     */
    public function create(TicketData $data, User $user): Ticket
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

    /**
     * Create a ticket from Portal form data (validated array).
     *
     * Handles:
     *  - Automatic unit/property resolution
     *  - Automatic Sachbearbeiter/Administrator assignment
     *    (priority: renter's manager → property manager → first Sachbearbeiter → first Admin)
     *  - Firing the TicketCreated event
     */
    public function createFromPortal(array $validated, User $user, ?Renter $renter): Ticket
    {
        return DB::transaction(function () use ($validated, $user, $renter) {
            $unitId = $validated['unit_id'] ?? null;
            $propertyId = $validated['property_id'] ?? null;

            // Auto-select the single unit when the renter only has one.
            if (! $unitId && $renter && $renter->units()->count() === 1) {
                $unitId = $renter->units()->first()->id;
            }

            // Derive propertyId from the unit when not explicitly provided.
            if ($unitId) {
                $unit = Unit::with('property')->find($unitId);
                $propertyId = $unit?->property_id ?? $propertyId;
            }

            // Automatic Sachbearbeiter assignment (priority cascade):
            // 1. Renter's dedicated manager
            // 2. Property's assigned manager
            // 3. First available Sachbearbeiter
            // 4. First Administrator
            $assignedTo = $renter?->manager_id;

            if (! $assignedTo && $propertyId) {
                $assignedTo = Property::find($propertyId)?->manager_id;
            }

            if (! $assignedTo) {
                $assignedTo = User::role('Sachbearbeiter')->first()?->id
                    ?? User::role('Administrator')->first()?->id;
            }

            $ticket = Ticket::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'status' => 'open',
                'property_id' => $propertyId,
                'unit_id' => $unitId,
                'renter_id' => $renter?->id,
                'created_by' => $user->id,
                'assigned_to' => $assignedTo,
            ]);

            event(new TicketCreated($ticket));

            return $ticket;
        });
    }
}
