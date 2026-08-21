<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Message::class, 'message');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Message::query()->with(['sender', 'recipient']);

        if (! $user->hasAnyRole(['Administrator', 'Sachbearbeiter'])) {
            $query->where(function ($q) use ($user) {
                $q->where('to_user_id', $user->id)->orWhere('from_user_id', $user->id);
            });
        }

        if ($threadId = $request->get('thread_id')) {
            $query->where('thread_id', $threadId);
        }

        $perPage = (int) $request->get('per_page', 15);
        $messages = $query->latest()->paginate($perPage);

        return MessageResource::collection($messages);
    }

    public function store(StoreMessageRequest $request): MessageResource
    {
        $data = $request->validated();
        $data['from_user_id'] = $request->user()->id;
        $data['thread_id'] = $data['thread_id'] ?? Str::uuid()->toString();

        $message = Message::create($data);
        $message->load(['sender', 'recipient']);

        return new MessageResource($message);
    }

    public function show(Message $message): MessageResource
    {
        $message->load(['sender', 'recipient']);

        return new MessageResource($message);
    }

    public function markAsRead(Request $request, Message $message): MessageResource
    {
        $message->update(['read_at' => now()]);

        return new MessageResource($message->fresh(['sender', 'recipient']));
    }

    public function destroy(Message $message): JsonResponse
    {
        $message->delete();

        return response()->json([], 204);
    }
}
