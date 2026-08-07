<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\InvoiceResource\Pages\ViewInvoice;
use App\Filament\Resources\PurchaseOrderResource\Pages\ListPurchaseOrders;
use App\Filament\Resources\PurchaseOrderResource\Pages\ViewPurchaseOrder;
use App\Filament\Resources\SupplierResource\Pages\ListSuppliers;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->org = Organization::factory()->create();
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $this->admin = User::factory()->create([
        'organization_id' => $this->org->id,
        'is_active' => true,
    ]);
    $this->admin->assignRole('super_admin');
    $this->actingAs($this->admin);

    $this->supplier = Supplier::factory()->for($this->org, 'organization')->create();
});

it('renders the suppliers list page', function () {
    livewire(ListSuppliers::class)->assertSuccessful();
});

it('renders the purchase orders list and view pages', function () {
    $po = PurchaseOrder::create([
        'organization_id' => $this->org->id,
        'supplier_id' => $this->supplier->id,
        'date' => now(),
        'currency' => 'CLP',
        'amount_net' => 100000,
        'tax_amount' => 19000,
        'amount_total' => 119000,
        'status' => PurchaseOrderStatus::Borrador,
    ]);

    livewire(ListPurchaseOrders::class)->assertSuccessful();
    livewire(ViewPurchaseOrder::class, ['record' => $po->getRouteKey()])->assertSuccessful();
});

it('renders the invoices list and view pages', function () {
    $invoice = Invoice::create([
        'organization_id' => $this->org->id,
        'type' => InvoiceType::Incoming,
        'supplier_id' => $this->supplier->id,
        'date' => now(),
        'due_date' => now()->addDays(30),
        'currency' => 'CLP',
        'amount_net' => 100000,
        'tax_amount' => 19000,
        'amount_total' => 119000,
        'status' => InvoiceStatus::Pendiente,
    ]);

    livewire(ListInvoices::class)->assertSuccessful();
    livewire(ViewInvoice::class, ['record' => $invoice->getRouteKey()])->assertSuccessful();
});
