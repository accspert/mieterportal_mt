<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'floor' => $this->floor,
            'area' => $this->area,
            'rooms' => $this->rooms,
            'status' => $this->status,
            'property' => $this->whenLoaded('property', function () {
                return [
                    'id' => $this->property->id,
                    'name' => $this->property->name,
                ];
            }),
            'current_tenant' => $this->whenLoaded('currentTenant', function () {
                return $this->currentTenant ? ['id' => $this->currentTenant->id, 'first_name' => $this->currentTenant->first_name, 'last_name' => $this->currentTenant->last_name] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
