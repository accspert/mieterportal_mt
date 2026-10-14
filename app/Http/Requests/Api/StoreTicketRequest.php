<?php

namespace App\Http\Requests\Api;

use App\Models\Ticket;
use App\Rules\TenantScoped;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', Ticket::class);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'property_id' => ['nullable', TenantScoped::exists('properties')],
            'unit_id' => ['nullable', TenantScoped::exists('units')],
            'renter_id' => ['nullable', TenantScoped::exists('renters')],
            'tenant_id' => ['nullable', TenantScoped::exists('renters')],
        ];
    }
}
