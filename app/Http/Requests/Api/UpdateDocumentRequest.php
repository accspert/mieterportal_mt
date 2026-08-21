<?php

namespace App\Http\Requests\Api;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Document $document */
        $document = $this->route('document');

        return $this->user() && $this->user()->can('update', $document);
    }

    public function rules(): array
    {
        return [
            'filename' => 'sometimes|required|string|max:255',
            'category' => 'nullable|string|max:100',
            'version' => 'nullable|integer',
            'path' => 'nullable|string|max:255',
        ];
    }
}
