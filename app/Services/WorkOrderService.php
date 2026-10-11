<?php

namespace App\Services;

use App\Enums\EstimateStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Estimate;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use App\Models\WorkOrderChecklistItem;
use App\Models\WorkOrderEvent;
use App\Models\WorkOrderLineItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WorkOrderService
{
    public function __construct(
        private NumberingService $numbering,
        private DocumentCalculator $calculator,
        private ScheduleConflictService $conflicts,
        private ActivityRecorder $activity,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<int, int>  $technicianIds
     * @param  array<int, string>  $checklist
     */
    public function create(Team $team, User $actor, array $attributes, array $lines = [], array $technicianIds = [], array $checklist = []): WorkOrder
    {
        return DB::transaction(function () use ($team, $actor, $attributes, $lines, $technicianIds, $checklist) {
            $totals = $this->calculator->calculate($lines, '0.00', $attributes['tax_rate'] ?? null);
            $locationSnapshot = $this->snapshotAddress($attributes);

            $workOrder = WorkOrder::query()->create([
                ...$locationSnapshot,
                ...collect($attributes)->except(['tax_rate', 'technician_ids', 'checklist', 'line_items'])->all(),
                'team_id' => $team->id,
                'created_by' => $actor->id,
                'reference' => $this->numbering->next($team, 'work_order'),
                'status' => $attributes['scheduled_start_at'] ?? null ? WorkOrderStatus::Scheduled : WorkOrderStatus::Draft,
                'currency' => $attributes['currency'] ?? $team->settings?->currency ?? 'USD',
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
            ]);

            $this->syncLines($workOrder, $totals['lines']);
            $this->syncChecklist($workOrder, $checklist);
            $this->syncAssignments($workOrder, $technicianIds);
            $this->recordEvent($workOrder, $actor, 'created', null, $workOrder->status->value);

            return $workOrder->fresh(['lineItems', 'assignments.user', 'checklistItems', 'customer']);
        });
    }

    public function createFromEstimate(Estimate $estimate, User $actor, array $overrides = []): WorkOrder
    {
        if ($estimate->status !== EstimateStatus::Approved) {
            throw new InvalidArgumentException('Only approved estimates can become work orders.');
        }

        $lines = $estimate->lineItems->map(fn ($line) => [
            'description' => $line->description,
            'quantity' => $line->quantity,
            'unit_price' => $line->unit_price,
            'tax_rate' => $line->tax_rate,
        ])->all();

        $location = $estimate->location;

        return $this->create($estimate->team, $actor, [
            'customer_id' => $estimate->customer_id,
            'service_location_id' => $estimate->service_location_id,
            'service_request_id' => $estimate->service_request_id,
            'estimate_id' => $estimate->id,
            'title' => $overrides['title'] ?? 'Work for '.$estimate->reference,
            'description' => $overrides['description'] ?? $estimate->notes,
            'customer_notes' => $estimate->customer_notes,
            'priority' => $overrides['priority'] ?? 'normal',
            'scheduled_start_at' => $overrides['scheduled_start_at'] ?? null,
            'scheduled_end_at' => $overrides['scheduled_end_at'] ?? null,
            'service_address_line1' => $location?->address_line1,
            'service_address_line2' => $location?->address_line2,
            'service_city' => $location?->city,
            'service_region' => $location?->region,
            'service_postal_code' => $location?->postal_code,
            'service_country' => $location?->country,
            'currency' => $estimate->currency,
        ], $lines, $overrides['technician_ids'] ?? [], $overrides['checklist'] ?? []);
    }

    /**
     * @param  array<int, int>  $technicianIds
     */
    public function schedule(WorkOrder $workOrder, User $actor, Carbon $start, Carbon $end, array $technicianIds = []): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $actor, $start, $end, $technicianIds) {
            $this->assertNoConflicts($workOrder, $technicianIds ?: $workOrder->assignments()->pluck('user_id')->all(), $start, $end);

            $history = $workOrder->reschedule_history ?? [];
            if ($workOrder->scheduled_start_at) {
                $history[] = [
                    'from' => $workOrder->scheduled_start_at?->toIso8601String(),
                    'to' => $start->toIso8601String(),
                    'by' => $actor->id,
                    'at' => now()->toIso8601String(),
                ];
            }

            $this->transition($workOrder, WorkOrderStatus::Scheduled, $actor, [
                'scheduled_start_at' => $start,
                'scheduled_end_at' => $end,
                'reschedule_history' => $history,
            ], 'scheduled');

            if ($technicianIds !== []) {
                $this->syncAssignments($workOrder, $technicianIds, $start, $end);
            } else {
                $workOrder->assignments()->update([
                    'scheduled_start_at' => $start,
                    'scheduled_end_at' => $end,
                ]);
            }

            return $workOrder->fresh(['assignments.user']);
        });
    }

    /**
     * @param  array<int, int>  $technicianIds
     */
    public function assign(WorkOrder $workOrder, User $actor, array $technicianIds): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $actor, $technicianIds) {
            if ($workOrder->scheduled_start_at && $workOrder->scheduled_end_at) {
                $this->assertNoConflicts(
                    $workOrder,
                    $technicianIds,
                    $workOrder->scheduled_start_at,
                    $workOrder->scheduled_end_at,
                );
            }

            $this->syncAssignments(
                $workOrder,
                $technicianIds,
                $workOrder->scheduled_start_at,
                $workOrder->scheduled_end_at,
            );
            $this->recordEvent($workOrder, $actor, 'assigned', $workOrder->status->value, $workOrder->status->value);
            $this->activity->record($workOrder->team, $workOrder, 'work_order.assigned', $actor, [
                'technician_ids' => $technicianIds,
            ]);

            return $workOrder->fresh(['assignments.user']);
        });
    }

    public function start(WorkOrder $workOrder, User $actor): WorkOrder
    {
        return $this->transition($workOrder, WorkOrderStatus::InProgress, $actor, [
            'started_at' => $workOrder->started_at ?? now(),
        ], 'started');
    }

    public function pause(WorkOrder $workOrder, User $actor, ?string $reason = null): WorkOrder
    {
        return $this->transition($workOrder, WorkOrderStatus::Paused, $actor, [
            'incomplete_reason' => $reason,
        ], 'paused');
    }

    public function complete(WorkOrder $workOrder, User $actor, ?string $signoffName = null): WorkOrder
    {
        return $this->transition($workOrder, WorkOrderStatus::Completed, $actor, [
            'completed_at' => now(),
            'signoff_name' => $signoffName,
            'signed_off_at' => $signoffName ? now() : null,
        ], 'completed');
    }

    public function cancel(WorkOrder $workOrder, User $actor, string $reason): WorkOrder
    {
        return $this->transition($workOrder, WorkOrderStatus::Cancelled, $actor, [
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ], 'cancelled');
    }

    public function markInvoiced(WorkOrder $workOrder, User $actor): WorkOrder
    {
        return $this->transition($workOrder, WorkOrderStatus::Invoiced, $actor, [], 'invoiced');
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function syncLines(WorkOrder $workOrder, array $lines): void
    {
        foreach ($lines as $line) {
            WorkOrderLineItem::query()->create([
                'work_order_id' => $workOrder->id,
                'position' => $line['position'],
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'tax_rate' => $line['tax_rate'],
                'line_total' => $line['line_total'],
                'is_material' => $line['is_material'] ?? false,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $checklist
     */
    private function syncChecklist(WorkOrder $workOrder, array $checklist): void
    {
        foreach (array_values($checklist) as $index => $label) {
            if (! is_string($label) || trim($label) === '') {
                continue;
            }

            WorkOrderChecklistItem::query()->create([
                'work_order_id' => $workOrder->id,
                'label' => $label,
                'position' => $index,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $technicianIds
     */
    private function syncAssignments(WorkOrder $workOrder, array $technicianIds, mixed $start = null, mixed $end = null): void
    {
        $workOrder->assignments()->delete();

        foreach (array_values(array_unique($technicianIds)) as $index => $userId) {
            WorkOrderAssignment::query()->create([
                'team_id' => $workOrder->team_id,
                'work_order_id' => $workOrder->id,
                'user_id' => $userId,
                'is_lead' => $index === 0,
                'scheduled_start_at' => $start ?? $workOrder->scheduled_start_at,
                'scheduled_end_at' => $end ?? $workOrder->scheduled_end_at,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $technicianIds
     */
    private function assertNoConflicts(WorkOrder $workOrder, array $technicianIds, Carbon $start, Carbon $end): void
    {
        foreach ($technicianIds as $userId) {
            $conflicts = $this->conflicts->conflictsFor(
                $workOrder->team,
                (int) $userId,
                $start,
                $end,
                $workOrder->id,
            );

            if ($conflicts !== []) {
                throw new InvalidArgumentException('Technician assignment overlaps another scheduled job.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function snapshotAddress(array $attributes): array
    {
        return collect($attributes)->only([
            'service_address_line1',
            'service_address_line2',
            'service_city',
            'service_region',
            'service_postal_code',
            'service_country',
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function transition(WorkOrder $workOrder, WorkOrderStatus $next, User $actor, array $extra, string $type): WorkOrder
    {
        if (! $workOrder->status->canTransitionTo($next) && $workOrder->status !== $next) {
            throw new InvalidArgumentException("Cannot move work order from {$workOrder->status->value} to {$next->value}.");
        }

        return DB::transaction(function () use ($workOrder, $next, $actor, $extra, $type) {
            $from = $workOrder->status;
            $workOrder->fill($extra);
            $workOrder->status = $next;
            $workOrder->save();
            $this->recordEvent($workOrder, $actor, $type, $from->value, $next->value);
            $this->activity->record($workOrder->team, $workOrder, 'work_order.status_changed', $actor, [
                'from' => $from->value,
                'to' => $next->value,
            ]);

            return $workOrder->fresh();
        });
    }

    private function recordEvent(WorkOrder $workOrder, User $actor, string $type, ?string $from, ?string $to): void
    {
        WorkOrderEvent::query()->create([
            'team_id' => $workOrder->team_id,
            'work_order_id' => $workOrder->id,
            'user_id' => $actor->id,
            'type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'occurred_at' => now(),
        ]);
    }
}
