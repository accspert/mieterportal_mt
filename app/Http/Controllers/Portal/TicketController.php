<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ResolvesUserProfile;
use App\Models\Owner;
use App\Models\Renter;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use App\Rules\TenantScoped;
use App\Services\FileUploadService;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    use ResolvesUserProfile;

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $renter = $this->resolveRenter($user);
        $tenant = $renter;
        $owner = $this->resolveOwner($user);

        $query = Ticket::query()->with(['property', 'unit', 'assignee', 'renter']);

        if ($user->hasAnyRole(['Administrator', 'Sachbearbeiter'])) {
            // Staff/Admin sees all company tickets or filters by assignment
            if ($request->query('assigned') === 'me') {
                $query->where('assigned_to', $user->id);
            }
        } elseif ($renter) {
            // Strict renter isolation: Renter only sees their own tickets
            $query->where(function ($q) use ($renter, $user) {
                $q->where('renter_id', $renter->id)
                    ->orWhere('created_by', $user->id);
            });
        } elseif ($owner) {
            $propertyIds = $owner->properties()->pluck('id')->toArray();
            $query->whereIn('property_id', $propertyIds);
        } else {
            $query->where('created_by', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        $tickets = $query->latest()->paginate(10)->withQueryString();

        return view('portal.tickets.index', compact('tickets', 'user', 'tenant', 'owner'));
    }

    public function create(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $renter = $this->resolveRenter($user);
        $tenant = $renter;
        $owner = $this->resolveOwner($user);

        $availableUnits = collect();
        $availableProperties = collect();

        if ($renter) {
            $availableUnits = $renter->units()->with('property')->get();
        }

        if ($owner) {
            $availableProperties = $owner->properties()->with('units')->get();
        }

        return view('portal.tickets.create', compact('user', 'tenant', 'owner', 'availableUnits', 'availableProperties'));
    }

    public function store(Request $request, FileUploadService $uploadService, TicketService $ticketService): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $renter = $this->resolveRenter($user);
        $isStaff = $user->hasAnyRole(['Administrator', 'Sachbearbeiter']);
        ['units' => $allowedUnitIds, 'properties' => $allowedPropertyIds] = $this->ticketableIds($renter, $this->resolveOwner($user));

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'string', 'in:general,repair,billing,other'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'unit_id' => ['nullable', 'integer', TenantScoped::exists('units'), ...($isStaff ? [] : [Rule::in($allowedUnitIds)])],
            'property_id' => ['nullable', 'integer', TenantScoped::exists('properties'), ...($isStaff ? [] : [Rule::in($allowedPropertyIds)])],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx'],
        ]);

        $ticket = $ticketService->createFromPortal($validated, $user, $renter);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $uploadService->storeAttachment($file, $ticket, $user);
            }
        }

        return redirect()->route('portal.tickets.show', $ticket)
            ->with('success', 'Ihre Schadensmeldung wurde erfolgreich übermittelt und dem zuständigen Sachbearbeiter zugewiesen.');
    }

    /**
     * Units and properties the user may report tickets for: the renter's rented units
     * and the owner's properties (including their units).
     *
     * @return array{units: list<int>, properties: list<int>}
     */
    private function ticketableIds(?Renter $renter, ?Owner $owner): array
    {
        $rentedUnits = $renter?->units()->get(['id', 'property_id']) ?? collect();
        $ownedPropertyIds = $owner?->properties()->pluck('id') ?? collect();

        return [
            'units' => $rentedUnits->pluck('id')
                ->merge(Unit::whereIn('property_id', $ownedPropertyIds)->pluck('id'))
                ->unique()->values()->all(),
            'properties' => $rentedUnits->pluck('property_id')
                ->merge($ownedPropertyIds)
                ->filter()->unique()->values()->all(),
        ];
    }

    public function show(Ticket $ticket): View
    {
        /** @var User $user */
        $user = Auth::user();
        $renter = $this->resolveRenter($user);
        $owner = $this->resolveOwner($user);

        // Strict authorization check: Only own tickets or authorized staff/owner
        $isCreator = ($ticket->created_by === $user->id);
        $isTicketRenter = ($renter && (int) $ticket->renter_id === (int) $renter->id);
        $isPropertyOwner = ($owner && $owner->properties()->where('id', $ticket->property_id)->exists());
        $isStaff = $user->hasAnyRole(['Administrator', 'Sachbearbeiter']);

        if (! ($isCreator || $isTicketRenter || $isPropertyOwner || $isStaff)) {
            abort(403, 'Zugriff auf dieses Ticket verweigert.');
        }

        $ticket->load(['property', 'unit', 'renter', 'assignee', 'creator', 'attachments.uploader']);

        return view('portal.tickets.show', compact('ticket', 'user'));
    }
}
