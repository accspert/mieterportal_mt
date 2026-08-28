<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ResolvesUserProfile;
use App\Models\Invoice;
use App\Models\Unit;
use App\Models\User;
use App\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InvoiceController extends Controller
{
    use ResolvesUserProfile;

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $renter = $this->resolveRenter($user);
        $owner = $this->resolveOwner($user);

        $query = Invoice::query()->with(['unit', 'renter', 'owner']);

        if ($renter) {
            $unitIds = $renter->units()->pluck('id')->toArray();
            $query->where(function ($q) use ($renter, $unitIds) {
                $q->where('renter_id', $renter->id)
                    ->orWhereIn('unit_id', $unitIds);
            });
        } elseif ($owner) {
            $propertyIds = $owner->properties()->pluck('id')->toArray();
            $unitIds = Unit::whereIn('property_id', $propertyIds)->pluck('id')->toArray();
            $query->where(function ($q) use ($owner, $unitIds) {
                $q->where('owner_id', $owner->id)
                    ->orWhereIn('unit_id', $unitIds);
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        $baseQuery = clone $query;

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $invoices = $query->latest('due_date')->paginate(10)->withQueryString();

        $totals = [
            'open_amount' => (clone $baseQuery)->whereIn('status', ['sent', 'overdue'])->sum('amount'),
            'paid_amount' => (clone $baseQuery)->where('status', 'paid')->sum('amount'),
            'open_count' => (clone $baseQuery)->whereIn('status', ['sent', 'overdue'])->count(),
            'paid_count' => (clone $baseQuery)->where('status', 'paid')->count(),
            'total_count' => (clone $baseQuery)->count(),
        ];

        return view('portal.invoices.index', compact('invoices', 'totals', 'user'));
    }

    public function download(Invoice $invoice, InvoicePdfService $pdfService): SymfonyResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $renter = $this->resolveRenter($user);
        $owner = $this->resolveOwner($user);

        $authorized = ($renter && $invoice->renter_id === $renter->id) ||
            ($owner && $invoice->owner_id === $owner->id) ||
            $user->hasAnyRole(['Administrator', 'Sachbearbeiter']);

        if (! $authorized) {
            abort(403, 'Zugriff auf diesen Rechnungsbeleg verweigert.');
        }

        if ($invoice->pdf_path && Storage::disk('public')->exists($invoice->pdf_path)) {
            $filename = 'Rechnung_'.str_replace(['/', '\\', ' '], '_', $invoice->invoice_number).'.pdf';

            return Storage::disk('public')->download($invoice->pdf_path, $filename);
        }

        return $pdfService->downloadResponse($invoice);
    }
}
