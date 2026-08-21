<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition()
    {
        return [
            'property_id' => Property::factory(),
            'number' => (string) $this->faker->numberBetween(1, 999),
            'floor' => (string) $this->faker->numberBetween(0, 5),
            'area' => $this->faker->randomFloat(2, 20, 200),
            'rooms' => $this->faker->numberBetween(1, 6),
            'status' => $this->faker->randomElement(['vacant', 'occupied', 'maintenance', 'inactive']),
        ];
    }
}
