<?php

namespace App\Services;

use App\Enums\EstimateStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\TeamPermission;
use App\Enums\WorkOrderStatus;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\ServiceRequest;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use Carbon\CarbonInterface;

class DashboardMetrics
{
    /**
     * @return array<string, mixed>
     */
    public function for(Team $team, User $user, CarbonInterface $from, CarbonInterface $to): array
    {
        $viewAll = $user->hasTeamPermission($team, TeamPermission::ViewAllJobs);
        $financials = $user->hasTeamPermission($team, TeamPermission::ViewFinancials);

        $workOrders = WorkOrder::query()->forTeam($team);
        if (! $viewAll) {
            $workOrders->whereHas('assignments', fn ($query) => $query->where('user_id', $user->id));
        }

        $todayStart = now($team->settings?->timezone ?: config('app.timezone'))->startOfDay();
        $todayEnd = (clone $todayStart)->endOfDay();

        $metrics = [
            'new_requests' => ServiceRequest::query()->forTeam($team)->where('status', ServiceRequestStatus::New)->count(),
            'unassigned_jobs' => WorkOrder::query()->forTeam($team)->whereIn('status', [
                WorkOrderStatus::Draft->value,
                WorkOrderStatus::Scheduled->value,
            ])->whereDoesntHave('assignments')->count(),
            'todays_jobs' => (clone $workOrders)->whereBetween('scheduled_start_at', [$todayStart, $todayEnd])->count(),
            'in_progress' => (clone $workOrders)->where('status', WorkOrderStatus::InProgress)->count(),
            'overdue_jobs' => (clone $workOrders)->whereIn('status', [
                WorkOrderStatus::Scheduled->value,
                WorkOrderStatus::InProgress->value,
                WorkOrderStatus::Paused->value,
            ])->whereNotNull('scheduled_end_at')->where('scheduled_end_at', '<', now())->count(),
            'estimates_awaiting' => Estimate::query()->forTeam($team)->where('status', EstimateStatus::Sent)->count(),
            'awaiting_invoice' => WorkOrder::query()->forTeam($team)->where('status', WorkOrderStatus::Completed)->count(),
            'technician_workload' => WorkOrderAssignment::query()
                ->selectRaw('user_id, COUNT(*) as jobs')
                ->where('team_id', $team->id)
                ->whereHas('workOrder', fn ($query) => $query->whereIn('status', [
                    WorkOrderStatus::Scheduled->value,
                    WorkOrderStatus::InProgress->value,
                    WorkOrderStatus::Paused->value,
                ]))
                ->groupBy('user_id')
                ->with('user:id,name')
                ->get()
                ->map(fn (WorkOrderAssignment $row) => [
                    'user_id' => $row->user_id,
                    'name' => $row->user?->name,
                    'jobs' => (int) $row->jobs,
                ])->values(),
        ];

        if ($financials) {
            $metrics['outstanding_invoices'] = Invoice::query()->forTeam($team)->whereIn('status', [
                InvoiceStatus::Issued->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Overdue->value,
            ])->count();
            $metrics['outstanding_balance'] = Invoice::query()->forTeam($team)->whereIn('status', [
                InvoiceStatus::Issued->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Overdue->value,
            ])->sum('balance_due');
            $metrics['revenue'] = Invoice::query()
                ->forTeam($team)
                ->whereIn('status', [InvoiceStatus::Paid->value, InvoiceStatus::PartiallyPaid->value])
                ->whereBetween('issued_on', [$from->toDateString(), $to->toDateString()])
                ->sum('amount_paid');
            $metrics['completed_jobs'] = WorkOrder::query()
                ->forTeam($team)
                ->whereIn('status', [WorkOrderStatus::Completed->value, WorkOrderStatus::Invoiced->value])
                ->whereBetween('completed_at', [$from, $to])
                ->count();
        }

        return $metrics;
    }
}
