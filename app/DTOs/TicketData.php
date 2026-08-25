<?php

namespace App\DTOs;

class TicketData
{
    public string $title;

    public ?string $description;

    public ?int $property_id;

    public ?int $unit_id;

    public ?int $renter_id;

    public ?int $tenant_id;

    public string $priority;

    public function __construct(array $data)
    {
        $this->title = $data['title'];
        $this->description = $data['description'] ?? null;
        $this->property_id = $data['property_id'] ?? null;
        $this->unit_id = $data['unit_id'] ?? null;
        $this->renter_id = $data['renter_id'] ?? $data['tenant_id'] ?? null;
        $this->tenant_id = $this->renter_id;
        $this->priority = $data['priority'] ?? 'medium';
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'property_id' => $this->property_id,
            'unit_id' => $this->unit_id,
            'renter_id' => $this->renter_id,
            'tenant_id' => $this->renter_id,
            'priority' => $this->priority,
        ];
    }
}
