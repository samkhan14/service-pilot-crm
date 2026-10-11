<?php

namespace App\Http\Controllers\Crm;

use App\Enums\TeamPermission;
use App\Enums\WorkOrderStatus;
use App\Http\Controllers\Concerns\AuthorizesCrm;
use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnicianWorkspaceController extends Controller
{
    use AuthorizesCrm;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, TeamPermission::ViewWorkOrders);
        $team = $this->currentTeam($request);
        $user = $request->user();

        $jobs = WorkOrder::query()
            ->forTeam($team)
            ->whereHas('assignments', fn ($query) => $query->where('user_id', $user->id))
            ->with(['customer:id,name,phone', 'assignments'])
            ->whereIn('status', [
                WorkOrderStatus::Scheduled->value,
                WorkOrderStatus::InProgress->value,
                WorkOrderStatus::Paused->value,
            ])
            ->orderBy('scheduled_start_at')
            ->get();

        return Inertia::render('crm/technician/index', [
            'jobs' => $jobs,
        ]);
    }

    public function show(Request $request, $current_team, WorkOrder $workOrder): Response
    {
        $team = $this->currentTeam($request);
        abort_unless($this->canViewWorkOrder($request->user(), $team, $workOrder), 403);

        $workOrder->load(['customer:id,name,phone,email', 'checklistItems', 'lineItems', 'events']);

        return Inertia::render('crm/technician/show', [
            'workOrder' => $workOrder,
        ]);
    }
}
