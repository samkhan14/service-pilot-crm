<?php

use App\Enums\InvoiceStatus;
use App\Enums\TeamRole;
use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\WorkOrder;
use App\Models\WorkOrderLineItem;

test('completed work orders can be invoiced and paid without exceeding the balance', function () {
    $user = crmOwner();
    $team = $user->currentTeam;
    $customer = Customer::factory()->create(['team_id' => $team->id]);
    $job = WorkOrder::factory()->completed()->create([
        'team_id' => $team->id,
        'customer_id' => $customer->id,
        'subtotal' => '100.00',
        'total' => '100.00',
    ]);
    WorkOrderLineItem::query()->create([
        'work_order_id' => $job->id,
        'position' => 0,
        'description' => 'Service call',
        'quantity' => 1,
        'unit_price' => 100,
        'tax_rate' => 0,
        'line_total' => 100,
    ]);

    $this->actingAs($user)
        ->post(route('work-orders.invoice', [$team, $job]))
        ->assertRedirect();

    $invoice = Invoice::query()->forTeam($team)->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->total)->toBe('100.00')
        ->and($invoice->balance_due)->toBe('100.00')
        ->and($job->fresh()->status)->toBe(WorkOrderStatus::Invoiced);

    $this->actingAs($user)
        ->from(route('invoices.show', [$team, $invoice]))
        ->post(route('invoices.pay', [$team, $invoice]), [
            'amount' => 150,
            'method' => 'check',
        ])
        ->assertRedirect(route('invoices.show', [$team, $invoice]))
        ->assertSessionHasErrors('amount');

    $this->actingAs($user)
        ->post(route('invoices.pay', [$team, $invoice]), [
            'amount' => 40,
            'method' => 'check',
            'reference' => 'CHK-1',
            'idempotency_key' => 'pay-1',
        ])
        ->assertRedirect();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PartiallyPaid)
        ->and($invoice->fresh()->amount_paid)->toBe('40.00');

    $this->actingAs($user)
        ->post(route('invoices.pay', [$team, $invoice]), [
            'amount' => 40,
            'method' => 'check',
            'reference' => 'CHK-1-dup',
            'idempotency_key' => 'pay-1',
        ])
        ->assertRedirect();

    expect($invoice->fresh()->amount_paid)->toBe('40.00')
        ->and($invoice->payments()->count())->toBe(1);

    $this->actingAs($user)
        ->post(route('invoices.pay', [$team, $invoice]), [
            'amount' => 60,
            'method' => 'ach',
            'idempotency_key' => 'pay-2',
        ])
        ->assertRedirect();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->fresh()->balance_due)->toBe('0.00');

    $this->actingAs($user)
        ->from(route('work-orders.show', [$team, $job]))
        ->post(route('work-orders.invoice', [$team, $job]))
        ->assertRedirect()
        ->assertSessionHasErrors('invoice');
});

test('technicians cannot issue invoices', function () {
    $owner = crmOwner();
    $tech = crmMember($owner->currentTeam, TeamRole::Technician);
    $job = WorkOrder::factory()->completed()->create([
        'team_id' => $owner->currentTeam->id,
    ]);

    $this->actingAs($tech)
        ->post(route('work-orders.invoice', [$owner->currentTeam, $job]))
        ->assertForbidden();
});
