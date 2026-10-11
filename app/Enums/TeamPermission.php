<?php

namespace App\Enums;

enum TeamPermission: string
{
    case UpdateTeam = 'team:update';
    case DeleteTeam = 'team:delete';

    case AddMember = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';

    case ViewDashboard = 'crm:dashboard.view';
    case ViewReports = 'crm:reports.view';
    case ManageSettings = 'crm:settings.manage';

    case ViewCustomers = 'crm:customers.view';
    case ManageCustomers = 'crm:customers.manage';

    case ViewRequests = 'crm:requests.view';
    case ManageRequests = 'crm:requests.manage';

    case ViewEstimates = 'crm:estimates.view';
    case ManageEstimates = 'crm:estimates.manage';
    case ApproveEstimates = 'crm:estimates.approve';
    case ApplyDiscounts = 'crm:estimates.discount';

    case ViewWorkOrders = 'crm:work_orders.view';
    case ManageWorkOrders = 'crm:work_orders.manage';
    case AssignWorkOrders = 'crm:work_orders.assign';
    case ExecuteWorkOrders = 'crm:work_orders.execute';

    case ViewSchedule = 'crm:schedule.view';
    case ManageSchedule = 'crm:schedule.manage';

    case ViewInvoices = 'crm:invoices.view';
    case ManageInvoices = 'crm:invoices.manage';
    case RecordPayments = 'crm:payments.record';

    case ViewAllJobs = 'crm:jobs.view_all';
    case ViewFinancials = 'crm:financials.view';
}
