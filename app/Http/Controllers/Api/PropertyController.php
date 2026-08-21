<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePropertyRequest;
use App\Http\Requests\Api\UpdatePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Property::class, 'property');
    }

    public function index(Request $request)
    {
        $query = Property::query();
        if ($q = $request->get('search')) {
            $query->where('name', 'like', "%{$q}%")->orWhere('city', 'like', "%{$q}%");
        }

        $perPage = (int) $request->get('per_page', 15);
        $result = $query->paginate($perPage);

        return PropertyResource::collection($result);
    }

    public function store(StorePropertyRequest $request)
    {
        $property = Property::create($request->validated());

        return new PropertyResource($property);
    }

    public function show(Property $property)
    {
        return new PropertyResource($property);
    }

    public function update(UpdatePropertyRequest $request, Property $property)
    {
        $property->update($request->validated());

        return new PropertyResource($property->fresh());
    }

    public function destroy(Property $property)
    {
        $property->delete();

        return response()->json([], 204);
    }
}
