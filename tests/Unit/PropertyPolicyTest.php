<?php

/**
 * @property PropertyPolicy $policy
 */

use App\Models\Owner;
use App\Models\Property;
use App\Models\User;
use App\Policies\PropertyPolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Sachbearbeiter']);
    Permission::firstOrCreate(['name' => 'manage properties']);
    $this->policy = new PropertyPolicy;
});

it('allows sachbearbeiter to create property', function () {
    $user = User::factory()->create();
    $user->assignRole('Sachbearbeiter');

    expect($this->policy->create($user))->toBeTrue();
});

it('allows owner to view own property', function () {
    $user = User::factory()->create();
    $owner = Owner::factory()->create(['user_id' => $user->id]);
    $property = Property::factory()->create(['owner_id' => $owner->id]);

    expect($this->policy->view($user, $property))->toBeTrue();
});

it('prevents random user from deleting property', function () {
    $user = User::factory()->create();
    $property = Property::factory()->create();

    expect($this->policy->delete($user, $property))->toBeFalse();
});
