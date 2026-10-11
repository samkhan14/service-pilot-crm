<?php

use App\Models\ServiceRequest;
use App\Models\User;

test('public intake creates a request for a known organization', function () {
    $owner = crmOwner();
    $team = $owner->currentTeam;

    $this->post(route('public-intake.store'), [
        'organization_slug' => $team->slug,
        'contact_name' => 'Casey Whitaker',
        'contact_email' => 'casey.whitaker@example.net',
        'contact_phone' => '555-0118',
        'subject' => 'No heat on the second floor',
        'description' => 'Furnace cycles but upstairs rooms stay cold.',
        'city' => 'Harborline',
    ])->assertRedirect();

    $request = ServiceRequest::query()->forTeam($team)->first();

    expect($request)->not->toBeNull()
        ->and($request->source)->toBe('public_form')
        ->and($request->contact_email)->toBe('casey.whitaker@example.net');
});

test('public intake ignores duplicate submissions in the same hour', function () {
    $owner = crmOwner();
    $team = $owner->currentTeam;
    $payload = [
        'organization_slug' => $team->slug,
        'contact_name' => 'Casey Whitaker',
        'contact_email' => 'casey.whitaker@example.net',
        'subject' => 'No heat on the second floor',
        'description' => 'Furnace cycles but upstairs rooms stay cold.',
    ];

    $this->post(route('public-intake.store'), $payload)->assertRedirect();
    $this->post(route('public-intake.store'), $payload)->assertRedirect();

    expect(ServiceRequest::query()->forTeam($team)->count())->toBe(1);
});

test('public intake rejects privileged field injection', function () {
    $owner = crmOwner();

    $this->post(route('public-intake.store'), [
        'organization_slug' => $owner->currentTeam->slug,
        'contact_name' => 'Casey Whitaker',
        'contact_email' => 'casey.whitaker@example.net',
        'subject' => 'Injected',
        'description' => 'Should fail.',
        'status' => 'converted',
        'assigned_user_id' => $owner->id,
        'total' => '9999',
    ])->assertStatus(422);
});

test('guests can open the intake page', function () {
    $this->get(route('public-intake.create'))
        ->assertOk();
});
