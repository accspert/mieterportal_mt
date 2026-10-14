<?php

namespace App\Http\Requests\Api;

use App\Models\Invoice;
use App\Rules\TenantScoped;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Invoice $invoice */
        $invoice = $this->route('invoice');

        return $this->user() && $this->user()->can('update', $invoice);
    }

    public function rules(): array
    {
        /** @var Invoice $invoice */
        $invoice = $this->route('invoice');
        $invoiceId = $invoice ? $invoice->id : null;

        return [
            'invoice_number' => ['sometimes', 'required', 'string', 'max:255', TenantScoped::unique('invoices', 'invoice_number')->ignore($invoiceId)],
            'renter_id' => ['nullable', TenantScoped::exists('renters')],
            'tenant_id' => ['nullable', TenantScoped::exists('renters')],
            'owner_id' => ['nullable', TenantScoped::exists('owners')],
            'unit_id' => ['nullable', TenantScoped::exists('units')],
            'amount' => 'sometimes|required|numeric|min:0',
            'due_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:draft,sent,paid,overdue,cancelled',
            'pdf_path' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
