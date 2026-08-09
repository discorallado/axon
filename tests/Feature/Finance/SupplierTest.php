<?php

use App\Models\Organization;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('creates a supplier scoped to the organization', function () {
    $org = Organization::factory()->create();

    $supplier = Supplier::factory()->for($org, 'organization')->create(['name' => 'Proveedor Test']);

    expect($supplier->organization_id)->toBe($org->id)
        ->and($supplier->name)->toBe('Proveedor Test');
});

it('isolates suppliers by organization', function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $userA = User::factory()->create(['organization_id' => $orgA->id, 'is_active' => true]);
    $userA->assignRole('super_admin');

    Supplier::factory()->for($orgA, 'organization')->create();
    Supplier::factory()->for($orgB, 'organization')->create();

    $this->actingAs($userA);

    expect(Supplier::all())->toHaveCount(1);
});
