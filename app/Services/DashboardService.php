<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Announcement;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DashboardService
{
    /**
     * Build the full dashboard data payload for the given user.
     *
     * @return array{
     *   renter: ?Renter,
     *   owner: ?Owner,
     *   stats: array<string, int|float>,
     *   units: Collection,
     *   primaryUnit: ?Unit,
     *   properties: Collection,
     *   recentTickets: Collection,
     *   recentDocuments: Collection,
     *   recentInvoices: Collection,
     *   recentMessages: Collection,
     *   announcements: Collection,
     *   leaseContractDoc: ?Document,
     * }
     */
    public function dataFor(User $user, ?Renter $renter, ?Owner $owner): array
    {
        $recentMessages = Message::where(function ($q) use ($user) {
            $q->where('to_user_id', $user->id)->orWhere('from_user_id', $user->id);
        })
            ->with(['sender', 'recipient'])
            ->latest()
            ->take(5)
            ->get();

        if ($renter) {
            return $this->renterData($user, $renter, $recentMessages);
        }

        if ($owner) {
            return $this->ownerData($user, $owner, $recentMessages);
        }

        if ($user->hasAnyRole(['Administrator', 'Sachbearbeiter'])) {
            return $this->adminData($user, $recentMessages);
        }

        return $this->emptyData($user, $recentMessages);
    }

    /**
     * @param  Collection<int, Message>  $recentMessages
     * @return array<string, mixed>
     */
    private function renterData(User $user, Renter $renter, Collection $recentMessages): array
    {
        $units = $renter->units()->with('property')->get();
        $primaryUnit = $units->first();
        $unitIds = $units->pluck('id')->toArray();
        $propertyIds = $units->pluck('property_id')->filter()->unique()->toArray();

        $stats = [
            'active_tickets' => Ticket::where('renter_id', $renter->id)
                ->whereIn('status', ['open', 'in_progress', 'waiting'])
                ->count(),
            'unread_messages' => Message::where('to_user_id', $user->id)->whereNull('read_at')->count(),
            'open_invoices' => Invoice::where(function ($q) use ($renter, $unitIds) {
                $q->where('renter_id', $renter->id)->orWhereIn('unit_id', $unitIds);
            })->whereIn('status', ['sent', 'overdue', 'draft'])->count(),
            'documents_count' => $this->documentQuery($renter, null, $unitIds, $propertyIds)
                ->whereNotIn('category', ['invoice', 'utility_bill'])
                ->count(),
        ];

        $leaseContractDoc = Document::where(function ($q) use ($renter, $unitIds) {
            $q->where(fn ($s) => $s->whereIn('documentable_type', [Renter::class, Tenant::class, 'App\\Models\\Tenant'])->where('documentable_id', $renter->id))
                ->orWhere(fn ($s) => $s->where('documentable_type', Unit::class)->whereIn('documentable_id', $unitIds));
        })
            ->where(fn ($q) => $q->where('category', 'contract')->orWhere('filename', 'like', '%Mietvertrag%'))
            ->latest()
            ->first();

        $recentTickets = Ticket::where('renter_id', $renter->id)
            ->with('property')->latest()->take(5)->get();

        $recentDocuments = $this->documentQuery($renter, null, $unitIds, $propertyIds)
            ->whereNotIn('category', ['invoice', 'utility_bill'])
            ->latest()->take(5)->get();

        $recentInvoices = Invoice::where(function ($q) use ($renter, $unitIds) {
            $q->where('renter_id', $renter->id)->orWhereIn('unit_id', $unitIds);
        })->latest('due_date')->take(5)->get();

        $announcements = Announcement::query()
            ->active()->forProperties($propertyIds)
            ->with('property')->latest('starts_at')->get();

        return [
            'renter' => $renter,
            'owner' => null,
            'stats' => $stats,
            'units' => $units,
            'primaryUnit' => $primaryUnit,
            'properties' => collect(),
            'recentTickets' => $recentTickets,
            'recentDocuments' => $recentDocuments,
            'recentInvoices' => $recentInvoices,
            'recentMessages' => $recentMessages,
            'announcements' => $announcements,
            'leaseContractDoc' => $leaseContractDoc,
        ];
    }

    /**
     * @param  Collection<int, Message>  $recentMessages
     * @return array<string, mixed>
     */
    private function ownerData(User $user, Owner $owner, Collection $recentMessages): array
    {
        $properties = $owner->properties()->with(['units.currentRenter'])->get();
        $propertyIds = $properties->pluck('id')->toArray();
        $unitIds = Unit::whereIn('property_id', $propertyIds)->pluck('id')->toArray();

        $totalUnits = $properties->sum(fn ($p) => $p->units->count());
        $occupiedUnits = $properties->sum(fn ($p) => $p->units->whereNotNull('current_renter_id')->count());
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;

        $stats = [
            'total_properties' => $properties->count(),
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'occupancy_rate' => $occupancyRate,
            'active_tickets' => Ticket::whereIn('property_id', $propertyIds)
                ->whereIn('status', ['open', 'in_progress', 'waiting'])->count(),
            'open_invoices' => Invoice::where(function ($q) use ($owner, $unitIds) {
                $q->where('owner_id', $owner->id)->orWhereIn('unit_id', $unitIds);
            })->whereIn('status', ['sent', 'overdue'])->count(),
            'unread_messages' => Message::where('to_user_id', $user->id)->whereNull('read_at')->count(),
            'documents_count' => $this->documentQuery(null, $owner, [], $propertyIds)
                ->whereNotIn('category', ['invoice', 'utility_bill'])->count(),
        ];

        $recentTickets = Ticket::whereIn('property_id', $propertyIds)
            ->with(['property', 'renter'])->latest()->take(5)->get();
        $recentDocuments = $this->documentQuery(null, $owner, [], $propertyIds)
            ->whereNotIn('category', ['invoice', 'utility_bill'])->latest()->take(5)->get();
        $recentInvoices = Invoice::where(function ($q) use ($owner, $unitIds) {
            $q->where('owner_id', $owner->id)->orWhereIn('unit_id', $unitIds);
        })->latest('due_date')->take(5)->get();

        $announcements = Announcement::query()
            ->active()->forProperties($propertyIds)
            ->with('property')->latest('starts_at')->get();

        return [
            'renter' => null,
            'owner' => $owner,
            'stats' => $stats,
            'units' => collect(),
            'primaryUnit' => null,
            'properties' => $properties,
            'recentTickets' => $recentTickets,
            'recentDocuments' => $recentDocuments,
            'recentInvoices' => $recentInvoices,
            'recentMessages' => $recentMessages,
            'announcements' => $announcements,
            'leaseContractDoc' => null,
        ];
    }

    /**
     * @param  Collection<int, Message>  $recentMessages
     * @return array<string, mixed>
     */
    private function adminData(User $user, Collection $recentMessages): array
    {
        $properties = Property::with(['units.currentRenter'])->get();
        $totalUnits = Unit::count();
        $occupiedUnits = Unit::whereNotNull('current_renter_id')->count();
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;

        $stats = [
            'total_properties' => $properties->count(),
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'occupancy_rate' => $occupancyRate,
            'active_tickets' => Ticket::whereIn('status', ['open', 'in_progress', 'waiting'])->count(),
            'open_invoices' => Invoice::whereIn('status', ['sent', 'overdue', 'draft'])->count(),
            'unread_messages' => Message::where('to_user_id', $user->id)->whereNull('read_at')->count(),
            'documents_count' => Document::whereNotIn('category', ['invoice', 'utility_bill'])->count(),
        ];

        $recentTickets = Ticket::with(['property', 'renter', 'assignee'])->latest()->take(5)->get();
        $recentDocuments = Document::with('documentable')->whereNotIn('category', ['invoice', 'utility_bill'])->latest()->take(5)->get();
        $recentInvoices = Invoice::with(['renter', 'unit', 'property'])->latest('due_date')->take(5)->get();
        $announcements = Announcement::query()->active()->with('property')->latest('starts_at')->get();

        return [
            'renter' => null,
            'owner' => null,
            'stats' => $stats,
            'units' => collect(),
            'primaryUnit' => null,
            'properties' => $properties,
            'recentTickets' => $recentTickets,
            'recentDocuments' => $recentDocuments,
            'recentInvoices' => $recentInvoices,
            'recentMessages' => $recentMessages,
            'announcements' => $announcements,
            'leaseContractDoc' => null,
        ];
    }

    /**
     * @param  Collection<int, Message>  $recentMessages
     * @return array<string, mixed>
     */
    private function emptyData(User $user, Collection $recentMessages): array
    {
        return [
            'renter' => null,
            'owner' => null,
            'stats' => [
                'active_tickets' => 0,
                'unread_messages' => Message::where('to_user_id', $user->id)->whereNull('read_at')->count(),
                'open_invoices' => 0,
                'documents_count' => 0,
                'total_properties' => 0,
                'total_units' => 0,
                'occupied_units' => 0,
                'occupancy_rate' => 0,
            ],
            'units' => collect(),
            'primaryUnit' => null,
            'properties' => collect(),
            'recentTickets' => collect(),
            'recentDocuments' => collect(),
            'recentInvoices' => collect(),
            'recentMessages' => $recentMessages,
            'announcements' => collect(),
            'leaseContractDoc' => null,
        ];
    }

    /**
     * Reusable document base query scoped to a renter and/or owner.
     *
     * @param  array<int>  $unitIds
     * @param  array<int>  $propertyIds
     */
    private function documentQuery(?Renter $renter, ?Owner $owner, array $unitIds, array $propertyIds): Builder
    {
        $query = Document::query();

        $query->where(function ($q) use ($renter, $owner, $unitIds, $propertyIds) {
            if ($renter) {
                $q->orWhere(fn ($s) => $s->whereIn('documentable_type', [Renter::class, Tenant::class, 'App\\Models\\Tenant'])->where('documentable_id', $renter->id));
            }

            if ($owner) {
                $q->orWhere(fn ($s) => $s->where('documentable_type', Owner::class)->where('documentable_id', $owner->id));
            }

            if (! empty($unitIds)) {
                $q->orWhere(fn ($s) => $s->where('documentable_type', Unit::class)->whereIn('documentable_id', $unitIds));
            }

            if (! empty($propertyIds)) {
                $q->orWhere(fn ($s) => $s->where('documentable_type', Property::class)->whereIn('documentable_id', $propertyIds));
            }
        });

        return $query;
    }
}
