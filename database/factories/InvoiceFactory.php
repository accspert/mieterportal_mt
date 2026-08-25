<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-'.strtoupper(fake()->bothify('####-????')),
            'renter_id' => null,
            'owner_id' => null,
            'unit_id' => null,
            'amount' => fake()->randomFloat(2, 50, 2500),
            'due_date' => fake()->dateTimeBetween('-1 month', '+2 months')->format('Y-m-d'),
            'status' => fake()->randomElement(['draft', 'sent', 'paid', 'overdue', 'cancelled']),
            'pdf_path' => null,
            'notes' => fake()->optional(0.6)->sentence(),
        ];
    }
}
