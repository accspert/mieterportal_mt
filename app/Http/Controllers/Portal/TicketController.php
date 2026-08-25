<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $renter = $user->renter ?? Renter::where('user_id', $user->id)->first() ?? Renter::where('email', $user->email)->first();
        $tenant = $renter;
        $owner = $user->owner ?? Owner::where('user_id', $user->id)->first() ?? Owner::where('email', $user->email)->first();

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
        $renter = $user->renter ?? Renter::where('user_id', $user->id)->first() ?? Renter::where('email', $user->email)->first();
        $tenant = $renter;
        $owner = $user->owner ?? Owner::where('user_id', $user->id)->first() ?? Owner::where('email', $user->email)->first();

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

    public function store(Request $request, FileUploadService $uploadService): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $renter = $user->renter ?? Renter::where('user_id', $user->id)->first() ?? Renter::where('email', $user->email)->first();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'string', 'in:general,repair,billing,other'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'property_id' => ['nullable', 'exists:properties,id'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx'],
        ]);

        $unitId = $validated['unit_id'] ?? null;
        if (! $unitId && $renter && $renter->units()->count() === 1) {
            $unitId = $renter->units()->first()->id;
        }

        $propertyId = $validated['property_id'] ?? null;
        if ($unitId) {
            $unit = Unit::with('property')->find($unitId);
            $propertyId = $unit?->property_id ?? $propertyId;
        }

        // Automatic Sachbearbeiter assignment:
        // 1. Check if renter has a dedicated assigned manager
        // 2. Check if property has an assigned manager
        // 3. Fallback to first available Sachbearbeiter or Admin
        $assignedTo = $renter?->manager_id;

        if (! $assignedTo && $propertyId) {
            $property = Property::find($propertyId);
            $assignedTo = $property?->manager_id;
        }

        if (! $assignedTo) {
            $assignedTo = User::role('Sachbearbeiter')->first()?->id ?? User::role('Administrator')->first()?->id;
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

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $uploadService->storeAttachment($file, $ticket, $user);
            }
        }

        event(new \App\Events\TicketCreated($ticket));

        return redirect()->route('portal.tickets.show', $ticket)
            ->with('success', 'Ihre Schadensmeldung wurde erfolgreich übermittelt und dem zuständigen Sachbearbeiter zugewiesen.');
    }

    public function show(Ticket $ticket): View
    {
        /** @var User $user */
        $user = Auth::user();
        $renter = $user->renter ?? Renter::where('user_id', $user->id)->first() ?? Renter::where('email', $user->email)->first();
        $owner = $user->owner ?? Owner::where('user_id', $user->id)->first() ?? Owner::where('email', $user->email)->first();

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
