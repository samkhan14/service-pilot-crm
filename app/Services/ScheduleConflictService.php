<?php

namespace App\Services;

use App\Enums\WorkOrderStatus;
use App\Models\Team;
use App\Models\WorkOrderAssignment;
use Carbon\CarbonInterface;

class ScheduleConflictService
{
    /**
     * @return array<int, array{work_order_id: int, reference: string, scheduled_start_at: string, scheduled_end_at: string}>
     */
    public function conflictsFor(
        Team $team,
        int $userId,
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $ignoreWorkOrderId = null,
    ): array {
        return WorkOrderAssignment::query()
            ->with('workOrder:id,reference,status')
            ->where('team_id', $team->id)
            ->where('user_id', $userId)
            ->when($ignoreWorkOrderId, fn ($query) => $query->where('work_order_id', '!=', $ignoreWorkOrderId))
            ->whereNotNull('scheduled_start_at')
            ->whereNotNull('scheduled_end_at')
            ->where('scheduled_start_at', '<', $end)
            ->where('scheduled_end_at', '>', $start)
            ->whereHas('workOrder', fn ($query) => $query->whereNotIn('status', [
                WorkOrderStatus::Cancelled->value,
                WorkOrderStatus::Completed->value,
                WorkOrderStatus::Invoiced->value,
            ]))
            ->get()
            ->map(fn (WorkOrderAssignment $assignment) => [
                'work_order_id' => $assignment->work_order_id,
                'reference' => $assignment->workOrder?->reference,
                'scheduled_start_at' => $assignment->scheduled_start_at?->toIso8601String(),
                'scheduled_end_at' => $assignment->scheduled_end_at?->toIso8601String(),
            ])
            ->all();
    }
}
