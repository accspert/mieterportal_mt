<?php

namespace App\Http\Requests\Api;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Announcement $announcement */
        $announcement = $this->route('announcement');

        return $this->user() && $this->user()->can('update', $announcement);
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:info,warning,urgent',
            'property_id' => 'nullable|exists:properties,id',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
        ];
    }
}
