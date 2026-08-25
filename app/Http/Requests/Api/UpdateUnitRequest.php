<?php

namespace App\Http\Requests\Api;

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
            'property_id' => 'nullable|exists:properties,id',
            'number' => 'nullable|string|max:50',
            'floor' => 'nullable|string|max:50',
            'area' => 'nullable|numeric',
            'rooms' => 'nullable|integer',
            'status' => 'nullable|in:vacant,occupied,maintenance,inactive',
            'current_renter_id' => 'nullable|exists:renters,id',
            'current_tenant_id' => 'nullable|exists:renters,id',
        ];
    }
}
