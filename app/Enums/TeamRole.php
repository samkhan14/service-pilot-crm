<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Operations = 'operations';
    case Dispatcher = 'dispatcher';
    case Sales = 'sales';
    case Technician = 'technician';
    case Accountant = 'accountant';
    case Viewer = 'viewer';
    case Member = 'member';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Organization Admin',
            self::Operations => 'Operations Manager',
            self::Dispatcher => 'Dispatcher',
            self::Sales => 'Sales Representative',
            self::Technician => 'Technician',
            self::Accountant => 'Accountant',
            self::Viewer => 'Read-only Manager',
            self::Member => 'Member',
        };
    }

    /**
     * Get all the permissions for this role.
     *
     * @return array<TeamPermission>
     */
    public function permissions(): array
    {
        $operational = [
            TeamPermission::ViewDashboard,
            TeamPermission::ViewReports,
            TeamPermission::ViewCustomers,
            TeamPermission::ManageCustomers,
            TeamPermission::ViewRequests,
            TeamPermission::ManageRequests,
            TeamPermission::ViewEstimates,
            TeamPermission::ManageEstimates,
            TeamPermission::ViewWorkOrders,
            TeamPermission::ManageWorkOrders,
            TeamPermission::AssignWorkOrders,
            TeamPermission::ViewSchedule,
            TeamPermission::ManageSchedule,
            TeamPermission::ViewAllJobs,
        ];

        return match ($this) {
            self::Owner => TeamPermission::cases(),
            self::Admin => array_values(array_filter(
                TeamPermission::cases(),
                fn (TeamPermission $permission) => ! in_array($permission, [
                    TeamPermission::DeleteTeam,
                    TeamPermission::AddMember,
                    TeamPermission::UpdateMember,
                    TeamPermission::RemoveMember,
                ], true),
            )),
            self::Operations => [
                ...$operational,
                TeamPermission::ApproveEstimates,
                TeamPermission::ApplyDiscounts,
                TeamPermission::ExecuteWorkOrders,
                TeamPermission::ViewInvoices,
                TeamPermission::ViewFinancials,
            ],
            self::Dispatcher => [
                TeamPermission::ViewDashboard,
                TeamPermission::ViewCustomers,
                TeamPermission::ViewRequests,
                TeamPermission::ManageRequests,
                TeamPermission::ViewWorkOrders,
                TeamPermission::ManageWorkOrders,
                TeamPermission::AssignWorkOrders,
                TeamPermission::ViewSchedule,
                TeamPermission::ManageSchedule,
                TeamPermission::ViewAllJobs,
            ],
            self::Sales => [
                TeamPermission::ViewDashboard,
                TeamPermission::ViewCustomers,
                TeamPermission::ManageCustomers,
                TeamPermission::ViewRequests,
                TeamPermission::ManageRequests,
                TeamPermission::ViewEstimates,
                TeamPermission::ManageEstimates,
                TeamPermission::ApproveEstimates,
                TeamPermission::ViewWorkOrders,
            ],
            self::Technician => [
                TeamPermission::ViewDashboard,
                TeamPermission::ViewWorkOrders,
                TeamPermission::ExecuteWorkOrders,
                TeamPermission::ViewSchedule,
            ],
            self::Accountant => [
                TeamPermission::ViewDashboard,
                TeamPermission::ViewReports,
                TeamPermission::ViewCustomers,
                TeamPermission::ViewEstimates,
                TeamPermission::ViewWorkOrders,
                TeamPermission::ViewInvoices,
                TeamPermission::ManageInvoices,
                TeamPermission::RecordPayments,
                TeamPermission::ViewFinancials,
            ],
            self::Viewer => [
                TeamPermission::ViewDashboard,
                TeamPermission::ViewReports,
                TeamPermission::ViewCustomers,
                TeamPermission::ViewRequests,
                TeamPermission::ViewEstimates,
                TeamPermission::ViewWorkOrders,
                TeamPermission::ViewSchedule,
                TeamPermission::ViewInvoices,
                TeamPermission::ViewAllJobs,
                TeamPermission::ViewFinancials,
            ],
            self::Member => [
                TeamPermission::ViewDashboard,
            ],
        };
    }

    /**
     * Determine if the role has the given permission.
     */
    public function hasPermission(TeamPermission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    /**
     * Get the hierarchy level for this role.
     * Higher numbers indicate higher privileges.
     */
    public function level(): int
    {
        return match ($this) {
            self::Owner => 9,
            self::Admin => 8,
            self::Operations => 7,
            self::Dispatcher => 6,
            self::Sales => 5,
            self::Accountant => 5,
            self::Technician => 3,
            self::Viewer => 2,
            self::Member => 1,
        };
    }

    /**
     * Check if this role is at least as privileged as another role.
     */
    public function isAtLeast(TeamRole $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Get the roles that can be assigned to team members (excludes Owner).
     *
     * @return array<array{value: string, label: string}>
     */
    public static function assignable(): array
    {
        return collect(self::cases())
            ->filter(fn (self $role) => $role !== self::Owner)
            ->map(fn (self $role) => ['value' => $role->value, 'label' => $role->label()])
            ->values()
            ->toArray();
    }
}
