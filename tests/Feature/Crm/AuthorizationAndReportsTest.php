<?php

use App\Enums\TeamRole;
use App\Models\Customer;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard remains available at the starter kit path', function () {
    $user = crmOwner();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard'));

    $this->actingAs($user)
        ->get(route('team.dashboard', $user->currentTeam))
        ->assertOk();
});

test('accountants can view reports and invoices but not manage customers', function () {
    $owner = crmOwner();
    $accountant = crmMember($owner->currentTeam, TeamRole::Accountant);

    $this->actingAs($accountant)
        ->get(route('reports.index', $owner->currentTeam))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('crm/reports/index'));

    $this->actingAs($accountant)
        ->get(route('invoices.index', $owner->currentTeam))
        ->assertOk();

    $this->actingAs($accountant)
        ->post(route('customers.store', $owner->currentTeam), [
            'name' => 'Blocked Customer',
        ])
        ->assertForbidden();
});

test('organization settings can be updated by owners', function () {
    $user = crmOwner();
    $team = $user->currentTeam;

    $this->actingAs($user)
        ->put(route('organization-settings.update', $team), [
            'legal_name' => 'Harborline Field Services LLC',
            'timezone' => 'America/New_York',
            'currency' => 'USD',
            'tax_label' => 'Sales tax',
            'default_tax_rate' => '8.25',
            'working_hours_start' => 8,
            'working_hours_end' => 17,
            'default_payment_terms_days' => 21,
            'payment_instructions' => 'Pay by check.',
        ])
        ->assertRedirect();

    expect($team->settings()->first()->default_payment_terms_days)->toBe(21)
        ->and($team->settings()->first()->legal_name)->toBe('Harborline Field Services LLC');
});

test('members cannot open organization settings', function () {
    $owner = crmOwner();
    $member = crmMember($owner->currentTeam, TeamRole::Member);

    $this->actingAs($member)
        ->get(route('organization-settings.edit', $owner->currentTeam))
        ->assertForbidden();
});

test('numbering service issues unique sequential document references', function () {
    $user = crmOwner();
    $team = $user->currentTeam;
    $customer = Customer::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user)->post(route('requests.store', $team), [
        'customer_id' => $customer->id,
        'subject' => 'First',
        'description' => 'One',
        'priority' => 'normal',
    ]);
    $this->actingAs($user)->post(route('requests.store', $team), [
        'customer_id' => $customer->id,
        'subject' => 'Second',
        'description' => 'Two',
        'priority' => 'normal',
    ]);

    $references = \App\Models\ServiceRequest::query()->forTeam($team)->pluck('reference');

    expect($references)->toHaveCount(2)
        ->and($references->unique())->toHaveCount(2)
        ->and($references->every(fn ($reference) => str_starts_with($reference, 'SR-')))->toBeTrue();
});
