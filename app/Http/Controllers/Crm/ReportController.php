<?php

namespace App\Http\Controllers\Crm;

use App\Enums\EstimateStatus;
use App\Enums\InvoiceStatus;
use App\Enums\TeamPermission;
use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Concerns\AuthorizesCrm;
use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    use AuthorizesCrm;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, TeamPermission::ViewReports);
        $team = $this->currentTeam($request);
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        $jobs = WorkOrder::query()->forTeam($team)->whereBetween('created_at', [$from, $to]);
        $completed = (clone $jobs)->whereIn('status', [WorkOrderStatus::Completed->value, WorkOrderStatus::Invoiced->value])->count();
        $totalJobs = (clone $jobs)->count();
        $estimates = Estimate::query()->forTeam($team)->whereBetween('created_at', [$from, $to]);
        $sent = (clone $estimates)->whereNotNull('sent_at')->count();
        $approved = (clone $estimates)->where('status', EstimateStatus::Approved)->count();

        $report = [
            'job_volume' => $totalJobs,
            'completion_rate' => $totalJobs === 0 ? 0 : round(($completed / $totalJobs) * 100, 1),
            'estimate_acceptance_rate' => $sent === 0 ? 0 : round(($approved / $sent) * 100, 1),
            'invoices_issued' => Invoice::query()->forTeam($team)->whereBetween('issued_on', [$from->toDateString(), $to->toDateString()])->count(),
            'outstanding_balance' => Invoice::query()->forTeam($team)->whereIn('status', [
                InvoiceStatus::Issued->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Overdue->value,
            ])->sum('balance_due'),
            'revenue' => Invoice::query()->forTeam($team)->whereBetween('issued_on', [$from->toDateString(), $to->toDateString()])->sum('amount_paid'),
            'technician_workload' => WorkOrderAssignment::query()
                ->selectRaw('user_id, COUNT(*) as jobs')
                ->where('team_id', $team->id)
                ->whereHas('workOrder', fn ($query) => $query->whereBetween('created_at', [$from, $to]))
                ->groupBy('user_id')
                ->with('user:id,name')
                ->get()
                ->map(fn (WorkOrderAssignment $row) => [
                    'name' => $row->user?->name,
                    'jobs' => (int) $row->jobs,
                ]),
        ];

        return Inertia::render('crm/reports/index', [
            'report' => $report,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }
}
