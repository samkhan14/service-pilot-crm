<?php

use App\Enums\EstimateStatus;
use App\Enums\TeamRole;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\EstimateLineItem;
use App\Models\WorkOrder;

test('estimate can be drafted sent approved and converted', function () {
    $user = crmOwner();
    $team = $user->currentTeam;
    $customer = Customer::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user)
        ->post(route('estimates.store', $team), [
            'customer_id' => $customer->id,
            'tax_rate' => '10',
            'discount_amount' => '0',
            'line_items' => [
                ['description' => 'Diagnostic', 'quantity' => 1, 'unit_price' => 100, 'tax_rate' => 10],
            ],
        ])
        ->assertRedirect();

    $estimate = Estimate::query()->forTeam($team)->first();

    expect($estimate->status)->toBe(EstimateStatus::Draft)
        ->and($estimate->total)->toBe('110.00');

    $this->actingAs($user)
        ->post(route('estimates.send', [$team, $estimate]))
        ->assertRedirect();

    expect($estimate->fresh()->status)->toBe(EstimateStatus::Sent);

    $this->actingAs($user)
        ->post(route('estimates.approve', [$team, $estimate]), [
            'approved_by_name' => 'Avery Kim',
        ])
        ->assertRedirect();

    expect($estimate->fresh()->status)->toBe(EstimateStatus::Approved)
        ->and($estimate->fresh()->approved_by_name)->toBe('Avery Kim');

    $this->actingAs($user)
        ->post(route('estimates.convert', [$team, $estimate]), [
            'title' => 'Approved diagnostic visit',
        ])
        ->assertRedirect();

    $workOrder = WorkOrder::query()->forTeam($team)->first();

    expect($workOrder)->not->toBeNull()
        ->and($workOrder->estimate_id)->toBe($estimate->id)
        ->and($workOrder->title)->toBe('Approved diagnostic visit');
});

test('technicians cannot approve estimates', function () {
    $owner = crmOwner();
    $tech = crmMember($owner->currentTeam, TeamRole::Technician);
    $estimate = Estimate::factory()->sent()->create([
        'team_id' => $owner->currentTeam->id,
    ]);
    EstimateLineItem::query()->create([
        'estimate_id' => $estimate->id,
        'position' => 0,
        'description' => 'Labor',
        'quantity' => 1,
        'unit_price' => 50,
        'tax_rate' => 0,
        'line_total' => 50,
    ]);

    $this->actingAs($tech)
        ->post(route('estimates.approve', [$owner->currentTeam, $estimate]))
        ->assertForbidden();
});

test('unapproved estimates cannot become work orders', function () {
    $user = crmOwner();
    $estimate = Estimate::factory()->create([
        'team_id' => $user->currentTeam->id,
    ]);

    $this->actingAs($user)
        ->from(route('estimates.show', [$user->currentTeam, $estimate]))
        ->post(route('estimates.convert', [$user->currentTeam, $estimate]))
        ->assertRedirect(route('estimates.show', [$user->currentTeam, $estimate]))
        ->assertSessionHasErrors('convert');

    expect(WorkOrder::query()->count())->toBe(0);
});
