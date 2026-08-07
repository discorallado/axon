<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseOrderStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function makePoForStateMachine(Organization $org): PurchaseOrder
{
    $supplier = Supplier::factory()->for($org, 'organization')->create();

    return PurchaseOrder::create([
        'organization_id' => $org->id,
        'supplier_id' => $supplier->id,
        'date' => now(),
        'currency' => 'CLP',
        'amount_net' => 100000,
        'tax_amount' => 19000,
        'amount_total' => 119000,
        'status' => PurchaseOrderStatus::Borrador,
    ]);
}

it('allows ingeniero to approve a purchase order and stamps approver', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('ingeniero', 'web');
    $user->assignRole('ingeniero');

    $po = makePoForStateMachine($org);

    $machine = app(PurchaseOrderStateMachine::class);
    $machine->transition($user, $po, PurchaseOrderStatus::Emitida, 'Aprobada');

    $fresh = $po->fresh();
    expect($fresh->status)->toBe(PurchaseOrderStatus::Emitida)
        ->and($fresh->approved_by)->toBe($user->id)
        ->and($fresh->approved_at)->not->toBeNull();

    $this->assertDatabaseHas('purchase_order_status_histories', [
        'purchase_order_id' => $po->id,
        'from_status' => PurchaseOrderStatus::Borrador->value,
        'to_status' => PurchaseOrderStatus::Emitida->value,
        'comment' => 'Aprobada',
    ]);
});

it('denies supervisor from approving a purchase order', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('supervisor', 'web');
    $user->assignRole('supervisor');

    $po = makePoForStateMachine($org);

    $machine = app(PurchaseOrderStateMachine::class);

    expect($machine->canTransition($user, $po, PurchaseOrderStatus::Emitida))->toBeFalse();
});

it('allows supervisor to mark a purchase order as received', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('supervisor', 'web');
    $user->assignRole('supervisor');

    $po = makePoForStateMachine($org);
    $po->update(['status' => PurchaseOrderStatus::Emitida]);

    $machine = app(PurchaseOrderStateMachine::class);
    $machine->transition($user, $po, PurchaseOrderStatus::Recibida);

    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Recibida);
});

it('blocks any transition from a terminal status', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('super_admin', 'web');
    $user->assignRole('super_admin');

    $po = makePoForStateMachine($org);
    $po->update(['status' => PurchaseOrderStatus::Recibida]);

    $machine = app(PurchaseOrderStateMachine::class);

    $this->expectException(HttpException::class);
    $machine->transition($user, $po, PurchaseOrderStatus::Anulada);
});

it('records status history on each transition', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('ingeniero', 'web');
    $user->assignRole('ingeniero');

    $po = makePoForStateMachine($org);

    $machine = app(PurchaseOrderStateMachine::class);
    $machine->transition($user, $po, PurchaseOrderStatus::Emitida);

    expect($po->statusHistories()->withoutGlobalScopes()->count())->toBe(1);
});
