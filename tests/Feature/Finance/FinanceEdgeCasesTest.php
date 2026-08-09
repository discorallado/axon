<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Resources\PurchaseOrderResource\Pages\CreatePurchaseOrder;
use App\Filament\Resources\PurchaseOrderResource\Pages\ListPurchaseOrders;
use App\Filament\Resources\SupplierResource;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'ingeniero', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'tecnico', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'calidad', 'guard_name' => 'web']);

    $this->orgA = Organization::factory()->create();
    $this->orgB = Organization::factory()->create();

    $this->ingenieroA = User::factory()->create(['organization_id' => $this->orgA->id, 'is_active' => true]);
    $this->ingenieroA->assignRole('ingeniero');
});

it('rejects creating a purchase order pointing at a supplier from another organization', function () {
    $supplierB = Supplier::factory()->for($this->orgB, 'organization')->create();

    $this->actingAs($this->ingenieroA);

    livewire(CreatePurchaseOrder::class)
        ->fillForm([
            'supplier_id' => $supplierB->id,
            'date' => now()->toDateString(),
            'currency' => 'CLP',
            'amount_net' => 100000,
            'tax_amount' => 19000,
            'amount_total' => 119000,
        ])
        ->call('create')
        ->assertHasFormErrors(['supplier_id']);

    expect(PurchaseOrder::withoutGlobalScopes()->count())->toBe(0);
});

it('rejects creating an invoice pointing at a project from another organization', function () {
    $projectB = Project::factory()->create(['organization_id' => $this->orgB->id]);
    $supplierA = Supplier::factory()->for($this->orgA, 'organization')->create();

    $this->actingAs($this->ingenieroA);

    livewire(CreateInvoice::class)
        ->fillForm([
            'type' => InvoiceType::Incoming->value,
            'supplier_id' => $supplierA->id,
            'project_id' => $projectB->id,
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'CLP',
            'amount_net' => 100000,
            'tax_amount' => 19000,
            'amount_total' => 119000,
        ])
        ->call('create')
        ->assertHasFormErrors(['project_id']);

    expect(Invoice::withoutGlobalScopes()->count())->toBe(0);
});

it('recalculates amount_total server-side even if the submitted value does not match net + tax', function () {
    $supplier = Supplier::factory()->for($this->orgA, 'organization')->create();

    $this->actingAs($this->ingenieroA);

    livewire(CreatePurchaseOrder::class)
        ->fillForm([
            'supplier_id' => $supplier->id,
            'date' => now()->toDateString(),
            'currency' => 'CLP',
            'amount_net' => 100000,
            'tax_amount' => 19000,
            'amount_total' => 999999,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $po = PurchaseOrder::first();

    expect((float) $po->amount_total)->toBe(119000.0);
});

it('toggles client_id/supplier_id visibility on the invoice form based on the selected type', function () {
    $this->actingAs($this->ingenieroA);

    livewire(CreateInvoice::class)
        ->assertFormFieldIsHidden('client_id')
        ->assertFormFieldIsHidden('supplier_id')
        ->set('data.type', InvoiceType::Outgoing->value)
        ->assertFormFieldIsVisible('client_id')
        ->assertFormFieldIsHidden('supplier_id')
        ->set('data.type', InvoiceType::Incoming->value)
        ->assertFormFieldIsHidden('client_id')
        ->assertFormFieldIsVisible('supplier_id');
});

it('rejects an outgoing invoice with no client selected', function () {
    $this->actingAs($this->ingenieroA);

    livewire(CreateInvoice::class)
        ->fillForm([
            'type' => InvoiceType::Outgoing->value,
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'CLP',
            'amount_net' => 100000,
            'tax_amount' => 19000,
            'amount_total' => 119000,
        ])
        ->call('create')
        ->assertHasFormErrors(['client_id']);

    expect(Invoice::withoutGlobalScopes()->count())->toBe(0);
});

it('allows the same code across two different organizations', function () {
    $supplierA = Supplier::factory()->for($this->orgA, 'organization')->create();
    $supplierB = Supplier::factory()->for($this->orgB, 'organization')->create();

    $poA = PurchaseOrder::unguarded(fn () => PurchaseOrder::create([
        'organization_id' => $this->orgA->id,
        'supplier_id' => $supplierA->id,
        'code' => 'OC-2026-001',
        'date' => now(),
        'currency' => 'CLP',
        'amount_net' => 100000,
        'tax_amount' => 19000,
        'amount_total' => 119000,
        'status' => PurchaseOrderStatus::Borrador,
    ]));

    $poB = PurchaseOrder::unguarded(fn () => PurchaseOrder::create([
        'organization_id' => $this->orgB->id,
        'supplier_id' => $supplierB->id,
        'code' => 'OC-2026-001',
        'date' => now(),
        'currency' => 'CLP',
        'amount_net' => 50000,
        'tax_amount' => 9500,
        'amount_total' => 59500,
        'status' => PurchaseOrderStatus::Borrador,
    ]));

    expect($poA->code)->toBe($poB->code);
});

it('denies tecnico from opening the purchase order create page', function () {
    $tecnico = User::factory()->create(['organization_id' => $this->orgA->id, 'is_active' => true]);
    $tecnico->assignRole('tecnico');
    $this->actingAs($tecnico);

    $this->get(PurchaseOrderResource::getUrl('create'))->assertForbidden();
});

it('denies calidad from opening the suppliers list page', function () {
    $calidad = User::factory()->create(['organization_id' => $this->orgA->id, 'is_active' => true]);
    $calidad->assignRole('calidad');
    $this->actingAs($calidad);

    $this->get(SupplierResource::getUrl('index'))->assertForbidden();
});

it('a supervisor cannot see purchase orders from another organization in the list', function () {
    $supervisorA = User::factory()->create(['organization_id' => $this->orgA->id, 'is_active' => true]);
    $supervisorA->assignRole('supervisor');

    $supplierA = Supplier::factory()->for($this->orgA, 'organization')->create();
    $supplierB = Supplier::factory()->for($this->orgB, 'organization')->create();

    PurchaseOrder::unguarded(fn () => PurchaseOrder::create([
        'organization_id' => $this->orgA->id,
        'supplier_id' => $supplierA->id,
        'date' => now(),
        'currency' => 'CLP',
        'amount_net' => 100000,
        'tax_amount' => 19000,
        'amount_total' => 119000,
        'status' => PurchaseOrderStatus::Borrador,
    ]));

    PurchaseOrder::unguarded(fn () => PurchaseOrder::create([
        'organization_id' => $this->orgB->id,
        'supplier_id' => $supplierB->id,
        'date' => now(),
        'currency' => 'CLP',
        'amount_net' => 50000,
        'tax_amount' => 9500,
        'amount_total' => 59500,
        'status' => PurchaseOrderStatus::Borrador,
    ]));

    $this->actingAs($supervisorA);

    livewire(ListPurchaseOrders::class)
        ->assertCanSeeTableRecords(PurchaseOrder::all())
        ->assertCountTableRecords(1);
});

it('does not let a direct mass-assignment update() bypass the invoice state machine', function () {
    $supplier = Supplier::factory()->for($this->orgA, 'organization')->create();

    $invoice = Invoice::unguarded(fn () => Invoice::create([
        'organization_id' => $this->orgA->id,
        'type' => InvoiceType::Incoming,
        'supplier_id' => $supplier->id,
        'date' => now(),
        'due_date' => now()->addDays(30),
        'currency' => 'CLP',
        'amount_net' => 100000,
        'tax_amount' => 19000,
        'amount_total' => 119000,
        'status' => InvoiceStatus::Pendiente,
    ]));

    $invoice->update(['status' => InvoiceStatus::Pagada]);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Pendiente);
});

it('does not let a direct mass-assignment update() bypass the purchase order state machine', function () {
    $supplier = Supplier::factory()->for($this->orgA, 'organization')->create();

    $po = PurchaseOrder::unguarded(fn () => PurchaseOrder::create([
        'organization_id' => $this->orgA->id,
        'supplier_id' => $supplier->id,
        'date' => now(),
        'currency' => 'CLP',
        'amount_net' => 100000,
        'tax_amount' => 19000,
        'amount_total' => 119000,
        'status' => PurchaseOrderStatus::Borrador,
    ]));

    $po->update(['status' => PurchaseOrderStatus::Recibida]);

    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Borrador);
});
