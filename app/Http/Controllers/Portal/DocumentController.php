<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Unit;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $renter = $user->renter ?? Renter::where('user_id', $user->id)->first() ?? Renter::where('email', $user->email)->first();
        $owner = $user->owner ?? Owner::where('user_id', $user->id)->first() ?? Owner::where('email', $user->email)->first();

        $query = Document::query();

        if ($renter) {
            $unitIds = $renter->units()->pluck('id')->toArray();
            $propertyIds = $renter->units()->pluck('property_id')->filter()->unique()->toArray();

            $query->where(function ($q) use ($renter, $unitIds, $propertyIds) {
                $q->where(fn ($sub) => $sub->where('documentable_type', Renter::class)->where('documentable_id', $renter->id))
                    ->orWhere(fn ($sub) => $sub->where('documentable_type', Unit::class)->whereIn('documentable_id', $unitIds))
                    ->orWhere(fn ($sub) => $sub->where('documentable_type', Property::class)->whereIn('documentable_id', $propertyIds));
            });
        } elseif ($owner) {
            $propertyIds = $owner->properties()->pluck('id')->toArray();

            $query->where(function ($q) use ($owner, $propertyIds) {
                $q->where(fn ($sub) => $sub->where('documentable_type', Owner::class)->where('documentable_id', $owner->id))
                    ->orWhere(fn ($sub) => $sub->where('documentable_type', Property::class)->whereIn('documentable_id', $propertyIds));
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        } else {
            $query->whereNotIn('category', ['invoice', 'utility_bill']);
        }

        if ($request->filled('search')) {
            $query->where('filename', 'like', '%'.$request->query('search').'%');
        }

        $documents = $query->latest()->paginate(12)->withQueryString();

        return view('portal.documents.index', compact('documents', 'user'));
    }

    public function download(Document $document): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $renter = $user->renter ?? Renter::where('user_id', $user->id)->first() ?? Renter::where('email', $user->email)->first();
        $tenant = $renter;
        $owner = $user->owner ?? Owner::where('user_id', $user->id)->first() ?? Owner::where('email', $user->email)->first();

        // Authorization check
        $authorized = false;
        if ($user->hasAnyRole(['Administrator', 'Sachbearbeiter'])) {
            $authorized = true;
        } elseif ($renter) {
            $unitIds = $renter->units()->pluck('id')->toArray();
            $propertyIds = $renter->units()->pluck('property_id')->filter()->unique()->toArray();

            if ($document->documentable_type === Renter::class && (int) $document->documentable_id === (int) $renter->id) {
                $authorized = true;
            } elseif ($document->documentable_type === Unit::class && in_array($document->documentable_id, $unitIds)) {
                $authorized = true;
            } elseif ($document->documentable_type === Property::class && in_array($document->documentable_id, $propertyIds)) {
                $authorized = true;
            }
        } elseif ($owner) {
            $propertyIds = $owner->properties()->pluck('id')->toArray();
            if ($document->documentable_type === Owner::class && (int) $document->documentable_id === (int) $owner->id) {
                $authorized = true;
            } elseif ($document->documentable_type === Property::class && in_array($document->documentable_id, $propertyIds)) {
                $authorized = true;
            }
        }

        if (! $authorized) {
            abort(403, 'Zugriff auf dieses Dokument verweigert.');
        }

        // 1. If physical file exists in storage, stream/download it directly
        if ($document->path && Storage::disk('public')->exists($document->path)) {
            return Storage::disk('public')->download($document->path, $document->filename, [
                'Content-Type' => $document->mime_type ?? 'application/pdf',
            ]);
        }

        // 2. Check attachments linked to document
        foreach ($document->attachments as $attachment) {
            if ($attachment->path && Storage::disk('public')->exists($attachment->path)) {
                return Storage::disk('public')->download($attachment->path, $attachment->filename ?: $document->filename, [
                    'Content-Type' => $attachment->mime_type ?? 'application/pdf',
                ]);
            }
        }

        // 3. Fallback: Generate branded PDF on the fly
        $document->loadMissing('documentable');
        $pdf = Pdf::loadView('pdf.document', [
            'document' => $document,
            'tenant' => $tenant,
            'owner' => $owner,
        ])->setPaper('a4', 'portrait');

        $downloadFilename = $document->filename;
        if (! str_ends_with(strtolower($downloadFilename), '.pdf')) {
            $downloadFilename .= '.pdf';
        }

        return $pdf->download($downloadFilename);
    }
}
