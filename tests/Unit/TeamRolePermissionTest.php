<?php

use App\Enums\TeamPermission;
use App\Enums\TeamRole;

test('admin cannot manage members or delete the organization', function () {
    $admin = TeamRole::Admin;

    expect($admin->hasPermission(TeamPermission::AddMember))->toBeFalse()
        ->and($admin->hasPermission(TeamPermission::UpdateMember))->toBeFalse()
        ->and($admin->hasPermission(TeamPermission::RemoveMember))->toBeFalse()
        ->and($admin->hasPermission(TeamPermission::DeleteTeam))->toBeFalse()
        ->and($admin->hasPermission(TeamPermission::ManageCustomers))->toBeTrue();
});

test('technician can execute assigned work but cannot view financials', function () {
    $tech = TeamRole::Technician;

    expect($tech->hasPermission(TeamPermission::ExecuteWorkOrders))->toBeTrue()
        ->and($tech->hasPermission(TeamPermission::ViewWorkOrders))->toBeTrue()
        ->and($tech->hasPermission(TeamPermission::ViewAllJobs))->toBeFalse()
        ->and($tech->hasPermission(TeamPermission::ViewFinancials))->toBeFalse()
        ->and($tech->hasPermission(TeamPermission::ManageInvoices))->toBeFalse();
});

test('owner has every permission', function () {
    foreach (TeamPermission::cases() as $permission) {
        expect(TeamRole::Owner->hasPermission($permission))->toBeTrue();
    }
});
