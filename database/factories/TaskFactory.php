<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'due_at' => fake()->optional(0.8)->dateTimeBetween('now', '+1 month'),
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
            'related_type' => Property::class,
            'related_id' => Property::factory(),
        ];
    }
}
