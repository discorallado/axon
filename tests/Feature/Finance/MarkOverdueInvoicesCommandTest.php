<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marks overdue pending invoices as vencida and leaves the rest untouched', function () {
    $org = Organization::factory()->create();
    $supplier = Supplier::factory()->for($org, 'organization')->create();

    // status no es mass-assignable a propósito (ver Invoice::$fillable); se
    // usa unguarded() para poder sembrar un estado inicial arbitrario en el test.
    $overdue = Invoice::unguarded(fn () => Invoice::create([
        'organization_id' => $org->id,
        'type' => InvoiceType::Incoming,
        'supplier_id' => $supplier->id,
        'date' => now()->subDays(40),
        'due_date' => now()->subDays(5),
        'currency' => 'CLP',
        'amount_net' => 100000,
        'tax_amount' => 19000,
        'amount_total' => 119000,
        'status' => InvoiceStatus::Pendiente,
    ]));

    $notYetDue = Invoice::unguarded(fn () => Invoice::create([
        'organization_id' => $org->id,
        'type' => InvoiceType::Incoming,
        'supplier_id' => $supplier->id,
        'date' => now(),
        'due_date' => now()->addDays(10),
        'currency' => 'CLP',
        'amount_net' => 50000,
        'tax_amount' => 9500,
        'amount_total' => 59500,
        'status' => InvoiceStatus::Pendiente,
    ]));

    $alreadyPaid = Invoice::unguarded(fn () => Invoice::create([
        'organization_id' => $org->id,
        'type' => InvoiceType::Incoming,
        'supplier_id' => $supplier->id,
        'date' => now()->subDays(40),
        'due_date' => now()->subDays(5),
        'currency' => 'CLP',
        'amount_net' => 80000,
        'tax_amount' => 15200,
        'amount_total' => 95200,
        'status' => InvoiceStatus::Pagada,
        'payment_date' => now()->subDays(10),
    ]));

    $this->artisan('invoices:mark-overdue')->assertSuccessful();

    expect($overdue->fresh()->status)->toBe(InvoiceStatus::Vencida)
        ->and($notYetDue->fresh()->status)->toBe(InvoiceStatus::Pendiente)
        ->and($alreadyPaid->fresh()->status)->toBe(InvoiceStatus::Pagada);
});
