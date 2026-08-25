<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $renter = $user->renter ?? Renter::where('user_id', $user->id)->first() ?? Renter::where('email', $user->email)->first();
        $tenant = $renter; // Backward-compatibility for dashboard view
        $owner = $user->owner ?? Owner::where('user_id', $user->id)->first() ?? Owner::where('email', $user->email)->first();

        $stats = [
            'active_tickets' => 0,
            'unread_messages' => Message::where('to_user_id', $user->id)->whereNull('read_at')->count(),
            'open_invoices' => 0,
            'documents_count' => 0,
            'total_properties' => 0,
            'total_units' => 0,
            'occupied_units' => 0,
            'occupancy_rate' => 0,
        ];
        $recentTickets = collect();
        $recentDocuments = collect();
        $recentInvoices = collect();
        $recentMessages = collect();
        $units = collect();
        $properties = collect();
        $announcements = collect();
        $leaseContractDoc = null;
        $primaryUnit = null;

        if ($renter) {
            $units = $renter->units()->with('property')->get();
            $primaryUnit = $units->first();
            $unitIds = $units->pluck('id')->toArray();
            $propertyIds = $units->pluck('property_id')->filter()->unique()->toArray();

            $stats = [
                'active_tickets' => Ticket::where('renter_id', $renter->id)
                    ->whereIn('status', ['open', 'in_progress', 'waiting'])
                    ->count(),
                'unread_messages' => Message::where('to_user_id', $user->id)
                    ->whereNull('read_at')
                    ->count(),
                'open_invoices' => Invoice::where(function ($q) use ($renter, $unitIds) {
                    $q->where('renter_id', $renter->id)
                        ->orWhereIn('unit_id', $unitIds);
                })
                    ->whereIn('status', ['sent', 'overdue', 'draft'])
                    ->count(),
                'documents_count' => Document::where(function ($q) use ($renter, $unitIds, $propertyIds) {
                    $q->where(fn ($sub) => $sub->where('documentable_type', Renter::class)->where('documentable_id', $renter->id))
                        ->orWhere(fn ($sub) => $sub->where('documentable_type', Unit::class)->whereIn('documentable_id', $unitIds))
                        ->orWhere(fn ($sub) => $sub->where('documentable_type', Property::class)->whereIn('documentable_id', $propertyIds));
                })
                    ->whereNotIn('category', ['invoice', 'utility_bill'])
                    ->count(),
            ];

            $leaseContractDoc = Document::where('documentable_type', Renter::class)
                ->where('documentable_id', $renter->id)
                ->where(function ($q) {
                    $q->where('category', 'contract')->orWhere('filename', 'like', '%Mietvertrag%');
                })
                ->latest()
                ->first();

            $recentTickets = Ticket::where('renter_id', $renter->id)
                ->with('property')
                ->latest()
                ->take(5)
                ->get();

            $recentDocuments = Document::where(function ($q) use ($renter, $unitIds, $propertyIds) {
                $q->where(fn ($sub) => $sub->where('documentable_type', Renter::class)->where('documentable_id', $renter->id))
                    ->orWhere(fn ($sub) => $sub->where('documentable_type', Unit::class)->whereIn('documentable_id', $unitIds))
                    ->orWhere(fn ($sub) => $sub->where('documentable_type', Property::class)->whereIn('documentable_id', $propertyIds));
            })
                ->whereNotIn('category', ['invoice', 'utility_bill'])
                ->latest()
                ->take(5)
                ->get();

            $recentInvoices = Invoice::where(function ($q) use ($renter, $unitIds) {
                $q->where('renter_id', $renter->id)
                    ->orWhereIn('unit_id', $unitIds);
            })
                ->latest('due_date')
                ->take(5)
                ->get();

            $announcements = Announcement::query()
                ->active()
                ->forProperties($propertyIds)
                ->with('property')
                ->latest('starts_at')
                ->get();
        } elseif ($owner) {
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
                    ->whereIn('status', ['open', 'in_progress', 'waiting'])
                    ->count(),
                'open_invoices' => Invoice::where(function ($q) use ($owner, $unitIds) {
                    $q->where('owner_id', $owner->id)
                        ->orWhereIn('unit_id', $unitIds);
                })
                    ->whereIn('status', ['sent', 'overdue'])
                    ->count(),
                'unread_messages' => Message::where('to_user_id', $user->id)
                    ->whereNull('read_at')
                    ->count(),
                'documents_count' => Document::where(function ($q) use ($owner, $propertyIds) {
                    $q->where(fn ($sub) => $sub->where('documentable_type', Owner::class)->where('documentable_id', $owner->id))
                        ->orWhere(fn ($sub) => $sub->where('documentable_type', Property::class)->whereIn('documentable_id', $propertyIds));
                })
                    ->whereNotIn('category', ['invoice', 'utility_bill'])
                    ->count(),
            ];

            $recentTickets = Ticket::whereIn('property_id', $propertyIds)
                ->with(['property', 'renter'])
                ->latest()
                ->take(5)
                ->get();

            $recentDocuments = Document::where(function ($q) use ($owner, $propertyIds) {
                $q->where(fn ($sub) => $sub->where('documentable_type', Owner::class)->where('documentable_id', $owner->id))
                    ->orWhere(fn ($sub) => $sub->where('documentable_type', Property::class)->whereIn('documentable_id', $propertyIds));
            })
                ->whereNotIn('category', ['invoice', 'utility_bill'])
                ->latest()
                ->take(5)
                ->get();

            $recentInvoices = Invoice::where(function ($q) use ($owner, $unitIds) {
                $q->where('owner_id', $owner->id)
                    ->orWhereIn('unit_id', $unitIds);
            })
                ->latest('due_date')
                ->take(5)
                ->get();

            $announcements = Announcement::query()
                ->active()
                ->forProperties($propertyIds)
                ->with('property')
                ->latest('starts_at')
                ->get();
        } elseif ($user->hasAnyRole(['Administrator', 'Sachbearbeiter'])) {
            $properties = Property::with(['units.currentRenter'])->get();
            $propertyIds = $properties->pluck('id')->toArray();
            $unitIds = Unit::pluck('id')->toArray();

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
                'unread_messages' => Message::where('to_user_id', $user->id)
                    ->whereNull('read_at')
                    ->count(),
                'documents_count' => Document::whereNotIn('category', ['invoice', 'utility_bill'])->count(),
            ];

            $recentTickets = Ticket::with(['property', 'renter', 'assignee'])
                ->latest()
                ->take(5)
                ->get();

            $recentDocuments = Document::whereNotIn('category', ['invoice', 'utility_bill'])
                ->latest()
                ->take(5)
                ->get();

            $recentInvoices = Invoice::latest('due_date')
                ->take(5)
                ->get();

            $announcements = Announcement::query()
                ->active()
                ->with('property')
                ->latest('starts_at')
                ->get();
        }

        $recentMessages = Message::where(function ($q) use ($user) {
            $q->where('to_user_id', $user->id)->orWhere('from_user_id', $user->id);
        })
            ->with(['sender', 'recipient'])
            ->latest()
            ->take(5)
            ->get();

        return view('portal.dashboard', compact(
            'user',
            'tenant',
            'owner',
            'stats',
            'units',
            'primaryUnit',
            'properties',
            'recentTickets',
            'recentDocuments',
            'recentInvoices',
            'recentMessages',
            'announcements',
            'leaseContractDoc'
        ));
    }
}
