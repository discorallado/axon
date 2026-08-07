<?php

use App\Models\Organization;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('denies tecnico and calidad from viewing finance data', function () {
    $org = Organization::factory()->create();
    Role::findOrCreate('tecnico', 'web');
    Role::findOrCreate('calidad', 'web');

    $tecnico = User::factory()->create(['organization_id' => $org->id]);
    $tecnico->assignRole('tecnico');

    $calidad = User::factory()->create(['organization_id' => $org->id]);
    $calidad->assignRole('calidad');

    expect($tecnico->can('viewAny', Supplier::class))->toBeFalse()
        ->and($calidad->can('viewAny', Supplier::class))->toBeFalse();
});

it('allows supervisor to view suppliers but not create them', function () {
    $org = Organization::factory()->create();
    Role::findOrCreate('supervisor', 'web');

    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    $supervisor->assignRole('supervisor');

    expect($supervisor->can('viewAny', Supplier::class))->toBeTrue()
        ->and($supervisor->can('create', Supplier::class))->toBeFalse();
});

it('allows ingeniero to create suppliers but not delete them', function () {
    $org = Organization::factory()->create();
    Role::findOrCreate('ingeniero', 'web');

    $ingeniero = User::factory()->create(['organization_id' => $org->id]);
    $ingeniero->assignRole('ingeniero');

    $supplier = Supplier::factory()->for($org, 'organization')->create();

    expect($ingeniero->can('create', Supplier::class))->toBeTrue()
        ->and($ingeniero->can('delete', $supplier))->toBeFalse();
});

it('only allows super_admin to delete a supplier', function () {
    $org = Organization::factory()->create();
    Role::findOrCreate('super_admin', 'web');

    $admin = User::factory()->create(['organization_id' => $org->id]);
    $admin->assignRole('super_admin');

    $supplier = Supplier::factory()->for($org, 'organization')->create();

    expect($admin->can('delete', $supplier))->toBeTrue();
});
