<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'documentable_type' => $this->faker->randomElement([
                Property::class,
                Renter::class,
                Unit::class,
            ]),
            'documentable_id' => 1,
            'filename' => $this->faker->bothify('doc-####??.pdf'),
            'path' => 'documents/'.$this->faker->uuid().'.pdf',
            'mime_type' => $this->faker->randomElement([
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
            ]),
            'size' => $this->faker->numberBetween(1024, 2048000),
            'category' => $this->faker->randomElement(['general', 'invoice', 'contract', 'maintenance']),
            'version' => (string) $this->faker->numberBetween(1, 5),
            'uploaded_by' => User::query()->inRandomOrder()->first()?->id ?? 1,
        ];
    }
}
