<?php

use App\Enums\TeamRole;
use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use App\Models\WorkOrderLineItem;

test('work orders can be created scheduled started and completed', function () {
    $user = crmOwner();
    $team = $user->currentTeam;
    $customer = Customer::factory()->create(['team_id' => $team->id]);
    $tech = crmMember($team, TeamRole::Technician);

    $this->actingAs($user)
        ->post(route('work-orders.store', $team), [
            'customer_id' => $customer->id,
            'title' => 'Walk-in cooler leak check',
            'description' => 'Inspect the bakery cooler.',
            'priority' => 'high',
            'technician_ids' => [$tech->id],
            'line_items' => [
                ['description' => 'Leak search', 'quantity' => 1, 'unit_price' => 240, 'tax_rate' => 0],
            ],
        ])
        ->assertRedirect();

    $job = WorkOrder::query()->forTeam($team)->first();

    expect($job->status)->toBe(WorkOrderStatus::Draft)
        ->and($job->total)->toBe('240.00');

    $start = now()->addDay()->setTime(9, 0);
    $end = now()->addDay()->setTime(11, 0);

    $this->actingAs($user)
        ->post(route('work-orders.schedule', [$team, $job]), [
            'scheduled_start_at' => $start->toDateTimeString(),
            'scheduled_end_at' => $end->toDateTimeString(),
            'technician_ids' => [$tech->id],
        ])
        ->assertRedirect();

    expect($job->fresh()->status)->toBe(WorkOrderStatus::Scheduled);

    $this->actingAs($tech)
        ->post(route('work-orders.start', [$team, $job]))
        ->assertRedirect();

    expect($job->fresh()->status)->toBe(WorkOrderStatus::InProgress);

    $this->actingAs($tech)
        ->post(route('work-orders.complete', [$team, $job]), [
            'signoff_name' => 'Theo Lang',
        ])
        ->assertRedirect();

    expect($job->fresh()->status)->toBe(WorkOrderStatus::Completed)
        ->and($job->fresh()->signoff_name)->toBe('Theo Lang');
});

test('overlapping technician assignments are rejected', function () {
    $user = crmOwner();
    $team = $user->currentTeam;
    $customer = Customer::factory()->create(['team_id' => $team->id]);
    $tech = crmMember($team, TeamRole::Technician);
    $start = now()->addDay()->setTime(9, 0);
    $end = now()->addDay()->setTime(11, 0);

    $first = WorkOrder::factory()->create([
        'team_id' => $team->id,
        'customer_id' => $customer->id,
        'status' => WorkOrderStatus::Scheduled,
        'scheduled_start_at' => $start,
        'scheduled_end_at' => $end,
    ]);
    WorkOrderAssignment::query()->create([
        'team_id' => $team->id,
        'work_order_id' => $first->id,
        'user_id' => $tech->id,
        'is_lead' => true,
        'scheduled_start_at' => $start,
        'scheduled_end_at' => $end,
    ]);

    $second = WorkOrder::factory()->create([
        'team_id' => $team->id,
        'customer_id' => $customer->id,
        'status' => WorkOrderStatus::Draft,
        'title' => 'Second call',
    ]);

    $this->actingAs($user)
        ->from(route('work-orders.show', [$team, $second]))
        ->post(route('work-orders.schedule', [$team, $second]), [
            'scheduled_start_at' => $start->copy()->addHour()->toDateTimeString(),
            'scheduled_end_at' => $end->copy()->addHour()->toDateTimeString(),
            'technician_ids' => [$tech->id],
        ])
        ->assertRedirect(route('work-orders.show', [$team, $second]))
        ->assertSessionHasErrors('scheduled_start_at');
});

test('technicians cannot view unassigned jobs', function () {
    $owner = crmOwner();
    $team = $owner->currentTeam;
    $assigned = crmMember($team, TeamRole::Technician);
    $other = crmMember($team, TeamRole::Technician);
    $job = WorkOrder::factory()->create(['team_id' => $team->id]);
    WorkOrderAssignment::query()->create([
        'team_id' => $team->id,
        'work_order_id' => $job->id,
        'user_id' => $assigned->id,
        'is_lead' => true,
    ]);

    $this->actingAs($other)
        ->get(route('work-orders.show', [$team, $job]))
        ->assertForbidden();

    $this->actingAs($assigned)
        ->get(route('work-orders.show', [$team, $job]))
        ->assertOk();
});
