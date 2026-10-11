<?php

namespace App\Http\Controllers\Crm;

use App\Enums\TeamPermission;
use App\Http\Controllers\Concerns\AuthorizesCrm;
use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    use AuthorizesCrm;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, TeamPermission::ViewSchedule);
        $team = $this->currentTeam($request);

        $start = Carbon::parse($request->input('from', now()->startOfWeek()->toDateString()))->startOfDay();
        $end = Carbon::parse($request->input('to', now()->endOfWeek()->toDateString()))->endOfDay();

        $jobs = WorkOrder::query()
            ->forTeam($team)
            ->with(['customer:id,name', 'assignments.user:id,name'])
            ->whereNotNull('scheduled_start_at')
            ->whereBetween('scheduled_start_at', [$start, $end])
            ->when($request->integer('technician_id'), function ($query, $technicianId) {
                $query->whereHas('assignments', fn ($inner) => $inner->where('user_id', $technicianId));
            })
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('scheduled_start_at')
            ->get();

        return Inertia::render('crm/schedule/index', [
            'jobs' => $jobs,
            'technicians' => $team->members()->orderBy('name')->get(['users.id', 'users.name']),
            'filters' => [
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                'technician_id' => $request->integer('technician_id') ?: null,
                'status' => $request->string('status')->toString() ?: null,
            ],
        ]);
    }
}
