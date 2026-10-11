<?php

namespace App\Services;

use App\Enums\EstimateStatus;
use App\Enums\ServiceRequestStatus;
use App\Models\Estimate;
use App\Models\EstimateLineItem;
use App\Models\Team;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EstimateService
{
    public function __construct(
        private NumberingService $numbering,
        private DocumentCalculator $calculator,
        private ActivityRecorder $activity,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function create(Team $team, User $actor, array $attributes, array $lines): Estimate
    {
        return DB::transaction(function () use ($team, $actor, $attributes, $lines) {
            $discount = Money::of($attributes['discount_amount'] ?? 0);
            $totals = $this->calculator->calculate($lines, $discount, $attributes['tax_rate'] ?? null);

            $estimate = Estimate::query()->create([
                ...$attributes,
                'team_id' => $team->id,
                'created_by' => $actor->id,
                'reference' => $this->numbering->next($team, 'estimate'),
                'status' => EstimateStatus::Draft,
                'currency' => $attributes['currency'] ?? $team->settings?->currency ?? 'USD',
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
            ]);

            $this->syncLines($estimate, $totals['lines']);
            $this->activity->record($team, $estimate, 'estimate.created', $actor);

            return $estimate->fresh(['lineItems', 'customer']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function updateDraft(Estimate $estimate, User $actor, array $attributes, array $lines): Estimate
    {
        if ($estimate->status !== EstimateStatus::Draft) {
            throw new InvalidArgumentException('Only draft estimates can be edited.');
        }

        return DB::transaction(function () use ($estimate, $actor, $attributes, $lines) {
            $discount = Money::of($attributes['discount_amount'] ?? $estimate->discount_amount);
            $totals = $this->calculator->calculate($lines, $discount, $attributes['tax_rate'] ?? $estimate->tax_rate);

            $estimate->fill($attributes);
            $estimate->fill([
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
            ])->save();

            $estimate->lineItems()->delete();
            $this->syncLines($estimate, $totals['lines']);
            $this->activity->record($estimate->team, $estimate, 'estimate.updated', $actor);

            return $estimate->fresh(['lineItems']);
        });
    }

    public function send(Estimate $estimate, User $actor): Estimate
    {
        $this->transition($estimate, EstimateStatus::Sent, $actor, [
            'sent_at' => now(),
            'issued_on' => $estimate->issued_on ?? now()->toDateString(),
        ]);

        return $estimate;
    }

    public function approve(Estimate $estimate, User $actor, ?string $approvedByName = null): Estimate
    {
        return DB::transaction(function () use ($estimate, $actor, $approvedByName) {
            $this->transition($estimate, EstimateStatus::Approved, $actor, [
                'approved_at' => now(),
                'approved_by_name' => $approvedByName ?: $actor->name,
                'accepted_snapshot' => [
                    'version' => $estimate->version,
                    'subtotal' => $estimate->subtotal,
                    'discount_amount' => $estimate->discount_amount,
                    'tax_amount' => $estimate->tax_amount,
                    'total' => $estimate->total,
                    'lines' => $estimate->lineItems()->get()->map->only([
                        'description', 'quantity', 'unit_price', 'tax_rate', 'line_total',
                    ])->all(),
                ],
            ]);

            if ($estimate->serviceRequest && $estimate->serviceRequest->status !== ServiceRequestStatus::Converted) {
                $estimate->serviceRequest->update([
                    'status' => ServiceRequestStatus::Converted,
                    'converted_at' => now(),
                ]);
            }

            return $estimate->fresh(['lineItems']);
        });
    }

    public function reject(Estimate $estimate, User $actor): Estimate
    {
        $this->transition($estimate, EstimateStatus::Rejected, $actor, [
            'rejected_at' => now(),
        ]);

        return $estimate;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function syncLines(Estimate $estimate, array $lines): void
    {
        foreach ($lines as $line) {
            EstimateLineItem::query()->create([
                'estimate_id' => $estimate->id,
                'position' => $line['position'],
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'tax_rate' => $line['tax_rate'],
                'line_total' => $line['line_total'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function transition(Estimate $estimate, EstimateStatus $next, User $actor, array $extra = []): void
    {
        if (! $estimate->status->canTransitionTo($next)) {
            throw new InvalidArgumentException("Cannot move estimate from {$estimate->status->value} to {$next->value}.");
        }

        $from = $estimate->status;
        $estimate->fill($extra);
        $estimate->status = $next;
        $estimate->save();

        $this->activity->record($estimate->team, $estimate, 'estimate.status_changed', $actor, [
            'from' => $from->value,
            'to' => $next->value,
        ]);
    }
}
