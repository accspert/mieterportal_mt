<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Document;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        return [
            'attachable_type' => $this->faker->randomElement([
                Document::class,
                Ticket::class,
            ]),
            'attachable_id' => 1,
            'filename' => $this->faker->bothify('attachment-####??.png'),
            'path' => 'attachments/'.$this->faker->uuid().'.png',
            'mime_type' => $this->faker->randomElement([
                'image/png',
                'image/jpeg',
                'application/pdf',
            ]),
            'size' => $this->faker->numberBetween(1024, 1200000),
            'uploaded_by' => User::query()->inRandomOrder()->first()?->id ?? 1,
        ];
    }
}
