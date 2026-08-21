<?php

namespace App\Http\Requests\Api;

use App\Models\Invoice;
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
            'invoice_number' => 'required|string|max:255|unique:invoices,invoice_number',
            'tenant_id' => 'nullable|exists:tenants,id',
            'owner_id' => 'nullable|exists:owners,id',
            'unit_id' => 'nullable|exists:units,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'status' => 'nullable|in:draft,sent,paid,overdue,cancelled',
            'pdf_path' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
