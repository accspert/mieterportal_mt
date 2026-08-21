<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDocumentRequest;
use App\Http\Requests\Api\UpdateDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
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
        $query = Document::query();

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
