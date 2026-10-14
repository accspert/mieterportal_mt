<?php

namespace Database\Factories;

use App\Models\Owner;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition()
    {
        return [
            'owner_id' => Owner::factory(),
            'name' => $this->faker->company.' - '.$this->faker->streetName,
            'street' => $this->faker->streetAddress,
            'postal_code' => $this->faker->postcode,
            'city' => $this->faker->city,
            'country' => 'CH',
            'description' => $this->faker->paragraph,
            'contact_person' => $this->faker->name,
        ];
    }
}
