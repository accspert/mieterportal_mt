<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
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
        $tenant = $user->tenant ?? \App\Models\Tenant::where('user_id', $user->id)->first() ?? \App\Models\Tenant::where('email', $user->email)->first();
        $owner = $user->owner ?? \App\Models\Owner::where('user_id', $user->id)->first() ?? \App\Models\Owner::where('email', $user->email)->first();

        $query = Ticket::query()->with(['property', 'unit', 'assignee', 'tenant']);

        if ($tenant) {
            // Strict tenant isolation: Tenant only sees their own tickets
            $query->where(function ($q) use ($tenant, $user) {
                $q->where('tenant_id', $tenant->id)
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
        $tenant = $user->tenant ?? \App\Models\Tenant::where('user_id', $user->id)->first() ?? \App\Models\Tenant::where('email', $user->email)->first();
        $owner = $user->owner ?? \App\Models\Owner::where('user_id', $user->id)->first() ?? \App\Models\Owner::where('email', $user->email)->first();

        $availableUnits = collect();
        $availableProperties = collect();

        if ($tenant) {
            $availableUnits = $tenant->units()->with('property')->get();
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
        $tenant = $user->tenant ?? \App\Models\Tenant::where('user_id', $user->id)->first() ?? \App\Models\Tenant::where('email', $user->email)->first();

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
        if (! $unitId && $tenant && $tenant->units()->count() === 1) {
            $unitId = $tenant->units()->first()->id;
        }

        $propertyId = $validated['property_id'] ?? null;
        if ($unitId) {
            $unit = Unit::with('property')->find($unitId);
            $propertyId = $unit?->property_id ?? $propertyId;
        }

        // Automatic Sachbearbeiter assignment:
        // 1. Check if tenant has a dedicated assigned manager
        // 2. Check if property has an assigned manager
        // 3. Fallback to first available Sachbearbeiter or Admin
        $assignedTo = $tenant?->manager_id;

        if (! $assignedTo && $propertyId) {
            $property = \App\Models\Property::find($propertyId);
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
            'tenant_id' => $tenant?->id,
            'created_by' => $user->id,
            'assigned_to' => $assignedTo,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $uploadService->storeAttachment($file, $ticket, $user);
            }
        }

        return redirect()->route('portal.tickets.show', $ticket)
            ->with('success', 'Ihre Schadensmeldung wurde erfolgreich übermittelt und dem zuständigen Sachbearbeiter zugewiesen.');
    }

    public function show(Ticket $ticket): View
    {
        /** @var User $user */
        $user = Auth::user();
        $tenant = $user->tenant ?? \App\Models\Tenant::where('user_id', $user->id)->first() ?? \App\Models\Tenant::where('email', $user->email)->first();
        $owner = $user->owner ?? \App\Models\Owner::where('user_id', $user->id)->first() ?? \App\Models\Owner::where('email', $user->email)->first();

        // Strict authorization check: Only own tickets or authorized staff/owner
        $isCreator = ($ticket->created_by === $user->id);
        $isTicketTenant = ($tenant && (int) $ticket->tenant_id === (int) $tenant->id);
        $isPropertyOwner = ($owner && $owner->properties()->where('id', $ticket->property_id)->exists());
        $isStaff = $user->hasAnyRole(['Administrator', 'Sachbearbeiter']);

        if (! ($isCreator || $isTicketTenant || $isPropertyOwner || $isStaff)) {
            abort(403, 'Zugriff auf dieses Ticket verweigert.');
        }

        $ticket->load(['property', 'unit', 'tenant', 'assignee', 'creator', 'attachments.uploader']);

        return view('portal.tickets.show', compact('ticket', 'user'));
    }
}
