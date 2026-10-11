<?php

use App\Enums\TeamRole;
use App\Models\Customer;
use App\Models\ServiceRequest;
use Inertia\Testing\AssertableInertia as Assert;

test('owners can create a service request', function () {
    $user = crmOwner();
    $team = $user->currentTeam;
    $customer = Customer::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user)
        ->post(route('requests.store', $team), [
            'customer_id' => $customer->id,
            'subject' => 'No heat upstairs',
            'description' => 'Furnace runs but the second floor stays cold.',
            'priority' => 'high',
            'contact_name' => $customer->name,
            'contact_email' => $customer->email,
        ])
        ->assertRedirect();

    $request = ServiceRequest::query()->forTeam($team)->first();

    expect($request)->not->toBeNull()
        ->and($request->reference)->toStartWith('SR-')
        ->and($request->status->value)->toBe('new');

    $this->actingAs($user)
        ->get(route('requests.show', [$team, $request]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('crm/requests/show'));
});

test('viewers cannot create service requests', function () {
    $owner = crmOwner();
    $viewer = crmMember($owner->currentTeam, TeamRole::Viewer);

    $this->actingAs($viewer)
        ->post(route('requests.store', $owner->currentTeam), [
            'subject' => 'Blocked',
            'description' => 'Should be forbidden',
            'priority' => 'normal',
        ])
        ->assertForbidden();
});
