<?php

namespace App\Http\Requests\Api;

use App\Models\Message;
use App\Rules\TenantScoped;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', Message::class);
    }

    public function rules(): array
    {
        return [
            'to_user_id' => ['nullable', TenantScoped::exists('users')],
            'thread_id' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string|max:5000',
        ];
    }
}
