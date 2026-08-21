<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'thread_id' => Str::uuid()->toString(),
            'from_user_id' => User::factory(),
            'to_user_id' => User::factory(),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraphs(2, true),
            'read_at' => fake()->optional(0.7)->dateTimeThisMonth(),
        ];
    }
}
