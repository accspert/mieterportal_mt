<?php

namespace App\Http\Requests\Api;

use App\Models\Unit;
use App\Rules\TenantScoped;
use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', Unit::class);
    }

    public function rules(): array
    {
        return [
            'property_id' => ['required', TenantScoped::exists('properties')],
            'number' => 'nullable|string|max:50',
            'floor' => 'nullable|string|max:50',
            'area' => 'nullable|numeric',
            'rooms' => 'nullable|integer',
            'status' => 'nullable|in:vacant,occupied,maintenance,inactive',
            'current_renter_id' => ['nullable', TenantScoped::exists('renters')],
            'current_tenant_id' => ['nullable', TenantScoped::exists('renters')],
        ];
    }
}
