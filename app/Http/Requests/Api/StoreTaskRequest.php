<?php

namespace App\Http\Requests\Api;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', Task::class);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'due_at' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'related_type' => 'nullable|string|max:255',
            'related_id' => 'nullable|integer',
        ];
    }
}
