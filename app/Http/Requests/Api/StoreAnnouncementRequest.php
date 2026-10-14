<?php

namespace App\Http\Requests\Api;

use App\Models\Announcement;
use App\Rules\TenantScoped;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', Announcement::class);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|in:info,warning,urgent',
            'property_id' => ['nullable', TenantScoped::exists('properties')],
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable|boolean',
        ];
    }
}
