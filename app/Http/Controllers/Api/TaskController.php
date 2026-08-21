<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTaskRequest;
use App\Http\Requests\Api\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Task::class, 'task');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Task::query()->with(['assignee', 'creator']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $perPage = (int) $request->get('per_page', 15);
        $tasks = $query->latest()->paginate($perPage);

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request): TaskResource
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $task = Task::create($data);
        $task->load(['assignee', 'creator']);

        return new TaskResource($task);
    }

    public function show(Task $task): TaskResource
    {
        $task->load(['assignee', 'creator']);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());
        $task->load(['assignee', 'creator']);

        return new TaskResource($task);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json([], 204);
    }
}
