<?php

namespace App\Http\Controllers\Api;

use App\DTOs\TicketData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTicketRequest;
use App\Http\Requests\Api\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    protected TicketService $service;

    public function __construct(TicketService $service)
    {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Ticket::class, 'ticket');
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $query = Ticket::query()->with(['property', 'unit', 'renter']);
        if ($q = $request->get('search')) {
            $query->where('title', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%");
        }

        $perPage = (int) $request->get('per_page', 15);
        $result = $query->paginate($perPage);

        return TicketResource::collection($result);
    }

    public function store(StoreTicketRequest $request)
    {
        $dto = TicketData::fromArray($request->validated());
        $ticket = $this->service->create($dto, $request->user());

        return new TicketResource($ticket);
    }

    public function show(Ticket $ticket)
    {
        return new TicketResource($ticket);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        $ticket->update($request->validated());

        return new TicketResource($ticket->fresh());
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return response()->json([], 204);
    }
}
