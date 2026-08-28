<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\ResolvesUserProfile;
use App\Models\Document;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Tenant;
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
    use ResolvesUserProfile;

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $renter = $this->resolveRenter($user);
        $owner = $this->resolveOwner($user);

        $query = Document::query();

        if ($renter) {
            $unitIds = $renter->units()->pluck('id')->toArray();
            $propertyIds = $renter->units()->pluck('property_id')->filter()->unique()->toArray();

            $query->where(function ($q) use ($renter, $unitIds, $propertyIds) {
                $q->where(fn ($sub) => $sub->whereIn('documentable_type', [Renter::class, Tenant::class, 'App\\Models\\Tenant'])->where('documentable_id', $renter->id))
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

        // Delegate authorization to DocumentPolicy::download()
        $this->authorize('download', $document);

        $owner = $this->resolveOwner($user);
        $renter = $this->resolveRenter($user);
        $disk = Storage::disk('public');

        // 1. If physical file exists in storage, stream/download it directly with its correct filename and MIME type
        if ($document->path && $disk->exists($document->path)) {
            $downloadFilename = $document->download_filename;
            $mimeType = $document->mime_type ?: $disk->mimeType($document->path) ?: 'application/octet-stream';

            return $disk->download($document->path, $downloadFilename, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="'.addslashes($downloadFilename).'"',
            ]);
        }

        // 2. Check attachments linked to document
        foreach ($document->attachments as $attachment) {
            if ($attachment->path && $disk->exists($attachment->path)) {
                $attName = $attachment->download_filename;
                $attMime = $attachment->mime_type ?: $disk->mimeType($attachment->path) ?: 'application/octet-stream';

                return $disk->download($attachment->path, $attName, [
                    'Content-Type' => $attMime,
                    'Content-Disposition' => 'attachment; filename="'.addslashes($attName).'"',
                ]);
            }
        }

        // 3. Fallback: Generate branded PDF on the fly (for contracts or virtual docs)
        $document->loadMissing('documentable');
        $pdf = Pdf::loadView('pdf.document', [
            'document' => $document,
            'tenant' => $renter,
            'owner' => $owner,
        ])->setPaper('a4', 'portrait');

        $downloadFilename = $document->filename;
        if (! str_ends_with(strtolower($downloadFilename), '.pdf')) {
            $downloadFilename .= '.pdf';
        }

        return $pdf->download($downloadFilename);
    }
}
