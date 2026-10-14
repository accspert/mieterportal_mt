<?php

namespace App\Http\Requests\Api;

use App\Models\Invoice;
use App\Rules\TenantScoped;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', Invoice::class);
    }

    public function rules(): array
    {
        return [
            'invoice_number' => ['required', 'string', 'max:255', TenantScoped::unique('invoices', 'invoice_number')],
            'renter_id' => ['nullable', TenantScoped::exists('renters')],
            'tenant_id' => ['nullable', TenantScoped::exists('renters')],
            'owner_id' => ['nullable', TenantScoped::exists('owners')],
            'unit_id' => ['nullable', TenantScoped::exists('units')],
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'status' => 'nullable|in:draft,sent,paid,overdue,cancelled',
            'pdf_path' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
