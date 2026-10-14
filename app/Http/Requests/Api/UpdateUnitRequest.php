<?php

namespace App\Http\Requests\Api;

use App\Rules\TenantScoped;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('update', $this->route('unit'));
    }

    public function rules(): array
    {
        return [
            'property_id' => ['nullable', TenantScoped::exists('properties')],
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
