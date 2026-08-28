<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status,
            'property' => $this->whenLoaded('property', function () {
                return ['id' => $this->property->id, 'name' => $this->property->name];
            }),
            'unit' => $this->whenLoaded('unit', function () {
                return ['id' => $this->unit->id, 'number' => $this->unit->number];
            }),
            'renter' => $this->whenLoaded('renter', function () {
                return $this->renter ? ['id' => $this->renter->id, 'first_name' => $this->renter->first_name] : null;
            }),
            'created_by' => $this->created_by,
            'assigned_to' => $this->assigned_to,
            'closed_at' => $this->closed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
