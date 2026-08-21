<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreUnitRequest;
use App\Http\Requests\Api\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Unit::class, 'unit');
    }

    public function index(Request $request)
    {
        $query = Unit::query()->with('property');
        if ($q = $request->get('search')) {
            $query->where('number', 'like', "%{$q}%");
        }

        $perPage = (int) $request->get('per_page', 15);
        $result = $query->paginate($perPage);

        return UnitResource::collection($result);
    }

    public function store(StoreUnitRequest $request)
    {
        $unit = Unit::create($request->validated());

        return new UnitResource($unit);
    }

    public function show(Unit $unit)
    {
        return new UnitResource($unit);
    }

    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        $unit->update($request->validated());

        return new UnitResource($unit->fresh());
    }

    public function destroy(Unit $unit)
    {
        $unit->delete();

        return response()->json([], 204);
    }
}
