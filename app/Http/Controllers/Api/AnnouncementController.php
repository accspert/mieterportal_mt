<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAnnouncementRequest;
use App\Http\Requests\Api\UpdateAnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AnnouncementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Announcement::class, 'announcement');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Announcement::query()->with('property');

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        if ($propertyId = $request->get('property_id')) {
            $query->where(fn ($q) => $q->whereNull('property_id')->orWhere('property_id', $propertyId));
        }

        $perPage = (int) $request->get('per_page', 15);
        $announcements = $query->latest('starts_at')->paginate($perPage);

        return AnnouncementResource::collection($announcements);
    }

    public function store(StoreAnnouncementRequest $request): AnnouncementResource
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $announcement = Announcement::create($data);
        $announcement->load('property');

        return new AnnouncementResource($announcement);
    }

    public function show(Announcement $announcement): AnnouncementResource
    {
        $announcement->load('property');

        return new AnnouncementResource($announcement);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): AnnouncementResource
    {
        $announcement->update($request->validated());
        $announcement->load('property');

        return new AnnouncementResource($announcement);
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->delete();

        return response()->json([], 204);
    }
}
