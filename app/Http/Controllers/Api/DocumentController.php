<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDocumentRequest;
use App\Http\Requests\Api\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Document::class, 'document');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Document::query();

        // Non-admin users may only see their own documents (same logic as Portal).
        if (! $user->hasAnyRole(['Administrator', 'Sachbearbeiter'])) {
            $renter = $user->resolvedRenter();
            $owner = $user->resolvedOwner();

            if ($renter) {
                $unitIds = $renter->units()->pluck('id')->toArray();
                $propertyIds = $renter->units()->pluck('property_id')->filter()->unique()->toArray();

                $query->where(function ($q) use ($renter, $unitIds, $propertyIds) {
                    $q->where(fn ($s) => $s->whereIn('documentable_type', [Renter::class, Tenant::class, 'App\\Models\\Tenant'])->where('documentable_id', $renter->id))
                        ->orWhere(fn ($s) => $s->where('documentable_type', Unit::class)->whereIn('documentable_id', $unitIds))
                        ->orWhere(fn ($s) => $s->where('documentable_type', Property::class)->whereIn('documentable_id', $propertyIds));
                });
            } elseif ($owner) {
                $propertyIds = $owner->properties()->pluck('id')->toArray();

                $query->where(function ($q) use ($owner, $propertyIds) {
                    $q->where(fn ($s) => $s->where('documentable_type', Owner::class)->where('documentable_id', $owner->id))
                        ->orWhere(fn ($s) => $s->where('documentable_type', Property::class)->whereIn('documentable_id', $propertyIds));
                });
            } else {
                // No renter/owner profile → return empty result set.
                $query->whereRaw('1 = 0');
            }
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        if ($search = $request->get('search')) {
            $query->where('filename', 'like', "%{$search}%");
        }

        $perPage = (int) $request->get('per_page', 15);
        $documents = $query->latest()->paginate($perPage);

        return DocumentResource::collection($documents);
    }

    public function store(StoreDocumentRequest $request): DocumentResource
    {
        $data = $request->validated();
        $data['uploaded_by'] = $request->user()->id;

        $document = Document::create($data);

        return new DocumentResource($document);
    }

    public function show(Document $document): DocumentResource
    {
        return new DocumentResource($document);
    }

    public function update(UpdateDocumentRequest $request, Document $document): DocumentResource
    {
        $document->update($request->validated());

        return new DocumentResource($document);
    }

    public function destroy(Document $document): JsonResponse
    {
        $document->delete();

        return response()->json([], 204);
    }
}
