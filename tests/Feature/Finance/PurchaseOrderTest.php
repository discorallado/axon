<?php

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrderResource;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makePurchaseOrder(Organization $org, Supplier $supplier, array $overrides = []): PurchaseOrder
{
    return PurchaseOrder::create(array_merge([
        'organization_id' => $org->id,
        'supplier_id' => $supplier->id,
        'date' => now(),
        'currency' => 'CLP',
        'amount_net' => 100000,
        'tax_amount' => 19000,
        'amount_total' => 119000,
        'status' => PurchaseOrderStatus::Borrador,
    ], $overrides));
}

it('generates a sequential code per organization and year', function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id, 'is_active' => true]);
    $user->assignRole('super_admin');
    $this->actingAs($user);

    $supplier = Supplier::factory()->for($org, 'organization')->create();

    $first = makePurchaseOrder($org, $supplier);
    $second = makePurchaseOrder($org, $supplier);

    $year = now()->year;

    expect($first->code)->toBe("OC-{$year}-001")
        ->and($second->code)->toBe("OC-{$year}-002");
});

it('isolates purchase orders by organization', function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $userA = User::factory()->create(['organization_id' => $orgA->id, 'is_active' => true]);
    $userA->assignRole('super_admin');

    $supplierA = Supplier::factory()->for($orgA, 'organization')->create();
    $supplierB = Supplier::factory()->for($orgB, 'organization')->create();

    makePurchaseOrder($orgA, $supplierA);
    makePurchaseOrder($orgB, $supplierB);

    $this->actingAs($userA);

    expect(PurchaseOrder::all())->toHaveCount(1);
});

it('recalculateAmountTotal ignores a mismatched submitted total', function () {
    $data = PurchaseOrderResource::recalculateAmountTotal([
        'amount_net' => 100000,
        'tax_amount' => 19000,
        'amount_total' => 1,
    ]);

    expect($data['amount_total'])->toBe(119000.0);
});

it('recalculateAmountTotal defaults missing net/tax to zero', function () {
    $data = PurchaseOrderResource::recalculateAmountTotal([
        'amount_total' => 500,
    ]);

    expect($data['amount_total'])->toBe(0.0);
});
