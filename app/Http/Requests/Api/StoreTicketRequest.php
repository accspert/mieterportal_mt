<?php

namespace App\Http\Requests\Api;

use App\Models\Ticket;
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
            'property_id' => 'nullable|exists:properties,id',
            'unit_id' => 'nullable|exists:units,id',
            'tenant_id' => 'nullable|exists:tenants,id',
        ];
    }
}
