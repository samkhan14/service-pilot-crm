<?php

use App\Models\Customer;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('owners can create and view a customer with a location', function () {
    $user = crmOwner();
    $team = $user->currentTeam;

    $this->actingAs($user)
        ->post(route('customers.store', $team), [
            'name' => 'Casey Whitaker',
            'company_name' => 'Whitaker Household',
            'email' => 'casey.whitaker@example.net',
            'phone' => '555-0118',
            'location_label' => 'Home',
            'location_address_line1' => '71 Cedar Slope',
            'location_city' => 'Harborline',
            'location_region' => 'NY',
            'location_postal_code' => '10013',
        ])
        ->assertRedirect();

    $customer = Customer::query()->where('email', 'casey.whitaker@example.net')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->team_id)->toBe($team->id)
        ->and($customer->locations)->toHaveCount(1);

    $this->actingAs($user)
        ->get(route('customers.show', [$team, $customer]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('crm/customers/show')
            ->where('customer.name', 'Casey Whitaker'));
});

test('possible duplicate customers require confirmation', function () {
    $user = crmOwner();
    $team = $user->currentTeam;
    Customer::factory()->create([
        'team_id' => $team->id,
        'name' => 'Casey Whitaker',
        'email' => 'casey.whitaker@example.net',
        'phone' => '555-0118',
    ]);

    $this->actingAs($user)
        ->from(route('customers.create', $team))
        ->post(route('customers.store', $team), [
            'name' => 'Casey Whitaker',
            'email' => 'casey.whitaker@example.net',
            'phone' => '555-0118',
        ])
        ->assertRedirect(route('customers.create', $team))
        ->assertSessionHasErrors('name');

    $this->actingAs($user)
        ->post(route('customers.store', $team), [
            'name' => 'Casey Whitaker',
            'email' => 'casey.whitaker@example.net',
            'phone' => '555-0118',
            'confirm_duplicate' => true,
        ])
        ->assertRedirect();

    expect(Customer::query()->forTeam($team)->where('email', 'casey.whitaker@example.net')->count())->toBe(2);
});

test('customers are scoped to the current organization', function () {
    $owner = crmOwner();
    $outsider = User::factory()->create();
    $customer = Customer::factory()->create(['team_id' => $owner->currentTeam->id]);

    $this->actingAs($outsider)
        ->get(route('customers.show', [$owner->currentTeam, $customer]))
        ->assertForbidden();

    $this->actingAs($outsider)
        ->get(route('customers.show', [$outsider->currentTeam, $customer]))
        ->assertNotFound();
});
