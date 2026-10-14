<?php

namespace App\Http\Requests\Api;

use App\Rules\TenantScoped;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('update', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:open,in_progress,waiting,done,archived',
            'property_id' => ['nullable', TenantScoped::exists('properties')],
            'unit_id' => ['nullable', TenantScoped::exists('units')],
            'renter_id' => ['nullable', TenantScoped::exists('renters')],
            'tenant_id' => ['nullable', TenantScoped::exists('renters')],
        ];
    }
}
