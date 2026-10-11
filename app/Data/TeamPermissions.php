<?php

namespace App\Data;

readonly class TeamPermissions
{
    public function __construct(
        public bool $canUpdateTeam,
        public bool $canDeleteTeam,
        public bool $canAddMember,
        public bool $canUpdateMember,
        public bool $canRemoveMember,
        public bool $canCreateInvitation,
        public bool $canCancelInvitation,
        public bool $canViewDashboard = true,
        public bool $canViewReports = false,
        public bool $canManageSettings = false,
        public bool $canViewCustomers = false,
        public bool $canManageCustomers = false,
        public bool $canViewRequests = false,
        public bool $canManageRequests = false,
        public bool $canViewEstimates = false,
        public bool $canManageEstimates = false,
        public bool $canApproveEstimates = false,
        public bool $canApplyDiscounts = false,
        public bool $canViewWorkOrders = false,
        public bool $canManageWorkOrders = false,
        public bool $canAssignWorkOrders = false,
        public bool $canExecuteWorkOrders = false,
        public bool $canViewSchedule = false,
        public bool $canManageSchedule = false,
        public bool $canViewInvoices = false,
        public bool $canManageInvoices = false,
        public bool $canRecordPayments = false,
        public bool $canViewAllJobs = false,
        public bool $canViewFinancials = false,
    ) {}
}
