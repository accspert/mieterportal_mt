<?php

namespace App\Http\Requests\Api;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Task $task */
        $task = $this->route('task');

        return $this->user() && $this->user()->can('update', $task);
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'sometimes|required|in:low,medium,high,urgent',
            'status' => 'sometimes|required|in:pending,in_progress,completed,cancelled',
            'due_at' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'related_type' => 'nullable|string|max:255',
            'related_id' => 'nullable|integer',
        ];
    }
}
