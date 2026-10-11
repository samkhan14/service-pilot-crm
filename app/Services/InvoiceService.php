<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Payment;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceService
{
    public function __construct(
        private NumberingService $numbering,
        private DocumentCalculator $calculator,
        private WorkOrderService $workOrders,
        private ActivityRecorder $activity,
    ) {}

    public function createFromWorkOrder(WorkOrder $workOrder, User $actor): Invoice
    {
        if ($workOrder->status !== WorkOrderStatus::Completed) {
            throw new InvalidArgumentException('Only completed work orders can be invoiced.');
        }

        return DB::transaction(function () use ($workOrder, $actor) {
            $existing = Invoice::query()
                ->where('work_order_id', $workOrder->id)
                ->where('status', '!=', InvoiceStatus::Void->value)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new InvalidArgumentException('This work order already has an invoice.');
            }

            $settings = $workOrder->team->settings()->first() ?? \App\Models\TeamSetting::forTeam($workOrder->team);
            $lines = $workOrder->lineItems->map(fn ($line) => [
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'tax_rate' => $line->tax_rate,
            ])->all();

            $totals = $this->calculator->calculate($lines);
            $invoice = Invoice::query()->create([
                'team_id' => $workOrder->team_id,
                'reference' => $this->numbering->next($workOrder->team, 'invoice'),
                'customer_id' => $workOrder->customer_id,
                'work_order_id' => $workOrder->id,
                'created_by' => $actor->id,
                'status' => InvoiceStatus::Issued,
                'currency' => $workOrder->currency,
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'amount_paid' => '0.00',
                'balance_due' => $totals['total'],
                'issued_on' => now()->toDateString(),
                'due_on' => now()->addDays($settings->default_payment_terms_days)->toDateString(),
                'payment_instructions' => $settings->payment_instructions,
                'issued_snapshot' => $totals,
            ]);

            foreach ($totals['lines'] as $line) {
                InvoiceLineItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'position' => $line['position'],
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'tax_rate' => $line['tax_rate'],
                    'line_total' => $line['line_total'],
                ]);
            }

            $this->workOrders->markInvoiced($workOrder, $actor);
            $this->activity->record($workOrder->team, $invoice, 'invoice.issued', $actor);

            return $invoice->fresh(['lineItems', 'customer']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function recordPayment(Invoice $invoice, User $actor, array $attributes): Payment
    {
        if (in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Void, InvoiceStatus::Paid], true)) {
            throw new InvalidArgumentException('Payments cannot be recorded on this invoice.');
        }

        $amount = Money::of($attributes['amount']);

        if (Money::isZero($amount) || Money::isNegative($amount)) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        if (Money::compare($amount, $invoice->balance_due) > 0) {
            throw new InvalidArgumentException('Payment exceeds the outstanding balance.');
        }

        return DB::transaction(function () use ($invoice, $actor, $attributes, $amount) {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if (! empty($attributes['idempotency_key'])) {
                $existing = Payment::query()
                    ->where('invoice_id', $invoice->id)
                    ->where('idempotency_key', $attributes['idempotency_key'])
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $payment = Payment::query()->create([
                'team_id' => $invoice->team_id,
                'invoice_id' => $invoice->id,
                'recorded_by' => $actor->id,
                'amount' => $amount,
                'method' => $attributes['method'] ?? 'manual',
                'reference' => $attributes['reference'] ?? null,
                'idempotency_key' => $attributes['idempotency_key'] ?? null,
                'paid_on' => $attributes['paid_on'] ?? now()->toDateString(),
                'notes' => $attributes['notes'] ?? null,
            ]);

            $invoice->refreshBalances();
            $this->activity->record($invoice->team, $invoice, 'payment.recorded', $actor, [
                'amount' => $amount,
                'payment_id' => $payment->id,
            ]);

            return $payment;
        });
    }
}
