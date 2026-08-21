<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'tenant_id' => $this->tenant_id,
            'owner_id' => $this->owner_id,
            'unit_id' => $this->unit_id,
            'amount' => (float) $this->amount,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'status' => $this->status,
            'pdf_path' => $this->pdf_path,
            'notes' => $this->notes,
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant->id,
                'name' => "{$this->tenant->first_name} {$this->tenant->last_name}",
                'email' => $this->tenant->email,
            ]),
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'company_name' => $this->owner->company_name,
                'name' => "{$this->owner->first_name} {$this->owner->last_name}",
            ]),
            'unit' => $this->whenLoaded('unit', fn () => [
                'id' => $this->unit->id,
                'number' => $this->unit->number,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
