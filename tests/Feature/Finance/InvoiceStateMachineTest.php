<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function makeInvoiceForStateMachine(Organization $org, array $overrides = []): Invoice
{
    $supplier = Supplier::factory()->for($org, 'organization')->create();

    // status no es mass-assignable a propósito (ver Invoice::$fillable); se
    // usa unguarded() para poder sembrar un estado inicial arbitrario en el test.
    return Invoice::unguarded(fn () => Invoice::create(array_merge([
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
    ], $overrides)));
}

it('allows ingeniero to mark an invoice as paid and stamps payment date', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('ingeniero', 'web');
    $user->assignRole('ingeniero');

    $invoice = makeInvoiceForStateMachine($org);

    $machine = app(InvoiceStateMachine::class);
    $machine->transition($user, $invoice, InvoiceStatus::Pagada);

    $fresh = $invoice->fresh();
    expect($fresh->status)->toBe(InvoiceStatus::Pagada)
        ->and($fresh->payment_date)->not->toBeNull();

    $this->assertDatabaseHas('invoice_status_histories', [
        'invoice_id' => $invoice->id,
        'from_status' => InvoiceStatus::Pendiente->value,
        'to_status' => InvoiceStatus::Pagada->value,
    ]);
});

it('denies supervisor from marking an invoice as paid', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('supervisor', 'web');
    $user->assignRole('supervisor');

    $invoice = makeInvoiceForStateMachine($org);

    $machine = app(InvoiceStateMachine::class);

    expect($machine->canTransition($user, $invoice, InvoiceStatus::Pagada))->toBeFalse();
});

it('never allows a user to transition an invoice directly to vencida', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('super_admin', 'web');
    $user->assignRole('super_admin');

    $invoice = makeInvoiceForStateMachine($org);

    $machine = app(InvoiceStateMachine::class);

    expect($machine->canTransition($user, $invoice, InvoiceStatus::Vencida))->toBeFalse()
        ->and($machine->allowedNextStatuses($invoice))->not->toContain(InvoiceStatus::Vencida);
});

it('blocks any transition from a terminal status', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('super_admin', 'web');
    $user->assignRole('super_admin');

    $invoice = makeInvoiceForStateMachine($org, ['status' => InvoiceStatus::Pagada]);

    $machine = app(InvoiceStateMachine::class);

    $this->expectException(HttpException::class);
    $machine->transition($user, $invoice, InvoiceStatus::Anulada);
});

it('marks a pending overdue invoice as vencida via markOverdue', function () {
    $org = Organization::factory()->create();
    $invoice = makeInvoiceForStateMachine($org, ['due_date' => now()->subDays(5)]);

    $machine = app(InvoiceStateMachine::class);
    $machine->markOverdue($invoice);

    $fresh = $invoice->fresh();
    expect($fresh->status)->toBe(InvoiceStatus::Vencida);

    $this->assertDatabaseHas('invoice_status_histories', [
        'invoice_id' => $invoice->id,
        'from_status' => InvoiceStatus::Pendiente->value,
        'to_status' => InvoiceStatus::Vencida->value,
        'changed_by' => null,
    ]);
});

it('does not mark a non-pending invoice as vencida', function () {
    $org = Organization::factory()->create();
    $invoice = makeInvoiceForStateMachine($org, [
        'due_date' => now()->subDays(5),
        'status' => InvoiceStatus::Pagada,
    ]);

    $machine = app(InvoiceStateMachine::class);
    $machine->markOverdue($invoice);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Pagada);
});
