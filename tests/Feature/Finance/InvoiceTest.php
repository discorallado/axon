<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeInvoice(Organization $org, Supplier $supplier, array $overrides = []): Invoice
{
    return Invoice::create(array_merge([
        'organization_id' => $org->id,
        'type' => InvoiceType::Incoming,
        'supplier_id' => $supplier->id,
        'date' => now(),
        'due_date' => now()->addDays(30),
        'currency' => 'CLP',
        'amount_net' => 100000,
        'tax_amount' => 19000,
        'amount_total' => 119000,
        'status' => InvoiceStatus::Pendiente,
    ], $overrides));
}

it('generates a sequential code per organization and year', function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id, 'is_active' => true]);
    $user->assignRole('super_admin');
    $this->actingAs($user);

    $supplier = Supplier::factory()->for($org, 'organization')->create();

    $first = makeInvoice($org, $supplier);
    $second = makeInvoice($org, $supplier);

    $year = now()->year;

    expect($first->code)->toBe("FC-{$year}-001")
        ->and($second->code)->toBe("FC-{$year}-002");
});

it('isolates invoices by organization', function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $userA = User::factory()->create(['organization_id' => $orgA->id, 'is_active' => true]);
    $userA->assignRole('super_admin');

    $supplierA = Supplier::factory()->for($orgA, 'organization')->create();
    $supplierB = Supplier::factory()->for($orgB, 'organization')->create();

    makeInvoice($orgA, $supplierA);
    makeInvoice($orgB, $supplierB);

    $this->actingAs($userA);

    expect(Invoice::all())->toHaveCount(1);
});

it('nulls out supplier_id when type is outgoing', function () {
    $data = InvoiceResource::normalizeTypeFields([
        'type' => InvoiceType::Outgoing->value,
        'client_id' => 'client-1',
        'supplier_id' => 'supplier-1',
    ]);

    expect($data['client_id'])->toBe('client-1')
        ->and($data['supplier_id'])->toBeNull();
});

it('nulls out client_id when type is incoming', function () {
    $data = InvoiceResource::normalizeTypeFields([
        'type' => InvoiceType::Incoming->value,
        'client_id' => 'client-1',
        'supplier_id' => 'supplier-1',
    ]);

    expect($data['supplier_id'])->toBe('supplier-1')
        ->and($data['client_id'])->toBeNull();
});
