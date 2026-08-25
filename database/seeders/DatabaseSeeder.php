<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Renter;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(InitialSetupSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@kundenportal.local'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        );
        $admin->assignRole('Administrator');

        $staff = User::firstOrCreate(
            ['email' => 'staff@kundenportal.local'],
            [
                'name' => 'Max Mustermann (Sachbearbeiter)',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        );
        $staff->assignRole('Sachbearbeiter');

        $staff2 = User::firstOrCreate(
            ['email' => 'ahmad.sahid@kundenportal.local'],
            [
                'name' => 'Ahmad Sahid',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        );
        $staff2->assignRole('Sachbearbeiter');

        $ownerUser = User::firstOrCreate(
            ['email' => 'owner@kundenportal.local'],
            [
                'name' => 'Eigentümer',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        );
        $ownerUser->assignRole('Eigentümer');

        $tenantUser = User::firstOrCreate(
            ['email' => 'tenant@kundenportal.local'],
            [
                'name' => 'Mieter',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        );
        $tenantUser->assignRole('Mieter');

        $owners = Owner::factory()->count(8)->create();

        foreach ($owners as $index => $owner) {
            $owner->update(['user_id' => $index === 0 ? $ownerUser->id : null]);
        }

        $properties = collect();
        foreach ($owners as $owner) {
            $properties = $properties->merge(
                Property::factory()->count(2)->create([
                    'owner_id' => $owner->id,
                    'manager_id' => $staff->id,
                ])
            );
        }

        $renters = Renter::factory()->count(18)->create();
        // Link the first renter firmly to tenantUser
        $renters->first()->update([
            'user_id' => $tenantUser->id,
            'first_name' => 'Maximilian',
            'last_name' => 'Mustermann',
            'email' => 'tenant@kundenportal.local',
        ]);

        $units = collect();
        foreach ($properties as $property) {
            $units = $units->merge(
                Unit::factory()->count(3)->create([
                    'property_id' => $property->id,
                ])
            );
        }

        foreach ($units as $index => $unit) {
            if ($renters->get($index % $renters->count())) {
                $unit->update(['current_renter_id' => $renters->get($index % $renters->count())->id]);
            }
        }

        $ticketUsers = User::query()->pluck('id');
        foreach ($properties as $property) {
            for ($i = 0; $i < 2; $i++) {
                $chosenRenter = $renters->random();
                Ticket::factory()->create([
                    'property_id' => $property->id,
                    'unit_id' => $property->units()->inRandomOrder()->first()?->id,
                    'renter_id' => $chosenRenter?->id,
                    'created_by' => $chosenRenter?->user_id ?? $ticketUsers->random(),
                    'assigned_to' => $property->manager_id ?? $staff->id,
                ]);
            }
        }

        $documentableModels = $properties->merge($renters)->merge($units);
        foreach ($documentableModels as $model) {
            for ($i = 0; $i < 2; $i++) {
                $document = Document::factory()->create([
                    'documentable_type' => $model::class,
                    'documentable_id' => $model->id,
                    'uploaded_by' => $admin->id,
                    'category' => $i === 0 ? 'general' : 'invoice',
                ]);

                Attachment::factory()->create([
                    'attachable_type' => $document::class,
                    'attachable_id' => $document->id,
                    'uploaded_by' => $admin->id,
                ]);
            }
        }

        // Seed Messages
        Message::factory()->count(10)->create([
            'from_user_id' => $tenantUser->id,
            'to_user_id' => $staff->id,
        ]);
        Message::factory()->count(5)->create([
            'from_user_id' => $ownerUser->id,
            'to_user_id' => $admin->id,
        ]);

        // Seed Tasks
        foreach ($properties->take(4) as $property) {
            Task::factory()->create([
                'created_by' => $admin->id,
                'assigned_to' => $staff->id,
                'related_type' => Property::class,
                'related_id' => $property->id,
            ]);
        }

        // Seed Invoices
        foreach ($units->take(6) as $index => $unit) {
            Invoice::factory()->create([
                'invoice_number' => 'RE-2026-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'renter_id' => $unit->current_renter_id,
                'unit_id' => $unit->id,
                'owner_id' => $unit->property->owner_id,
            ]);
        }
    }
}
