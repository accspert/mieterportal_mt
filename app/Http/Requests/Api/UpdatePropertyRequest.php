<?php

namespace App\Http\Requests\Api;

use App\Rules\TenantScoped;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('update', $this->route('property'));
    }

    public function rules(): array
    {
        return [
            'owner_id' => ['nullable', TenantScoped::exists('owners')],
            'name' => 'sometimes|required|string|max:255',
            'street' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
        ];
    }
}
