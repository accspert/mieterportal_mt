<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Message;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeedDataCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_fill_core_business_tables(): void
    {
        $this->artisan('db:seed')->assertOk();

        $this->assertGreaterThan(0, Role::count());
        $this->assertGreaterThan(0, Owner::count());
        $this->assertGreaterThan(0, Property::count());
        $this->assertGreaterThan(0, Tenant::count());
        $this->assertGreaterThan(0, Unit::count());
        $this->assertGreaterThan(0, Ticket::count());
        $this->assertGreaterThan(0, Message::count());
        $this->assertGreaterThan(0, Task::count());
        $this->assertGreaterThan(0, Invoice::count());
    }
}
