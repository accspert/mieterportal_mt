<?php

namespace App\Http\Requests\Api;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', Document::class);
    }

    public function rules(): array
    {
        return [
            'documentable_type' => 'required|string|max:255',
            'documentable_id' => 'required|integer',
            'filename' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'mime_type' => 'nullable|string|max:100',
            'size' => 'nullable|integer',
            'version' => 'nullable|integer',
            'category' => 'nullable|string|max:100',
        ];
    }
}
