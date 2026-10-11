<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\InvoiceStatus;
use App\Support\Money;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'team_id', 'reference', 'customer_id', 'work_order_id', 'created_by', 'status',
    'currency', 'subtotal', 'tax_amount', 'total', 'amount_paid', 'balance_due',
    'issued_on', 'due_on', 'notes', 'payment_instructions', 'issued_snapshot',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use BelongsToTeam, HasFactory;

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * @return HasMany<InvoiceLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class)->orderBy('position');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_on');
    }

    public function refreshBalances(): void
    {
        $paid = $this->payments()->get()->reduce(
            fn (string $carry, Payment $payment) => Money::add($carry, $payment->amount),
            '0.00',
        );

        $balance = Money::max(Money::subtract($this->total, $paid), '0');
        $status = $this->status;

        if ($status !== InvoiceStatus::Void && $status !== InvoiceStatus::Draft) {
            $status = match (true) {
                Money::isZero($paid) && $this->due_on?->isPast() => InvoiceStatus::Overdue,
                Money::isZero($paid) => InvoiceStatus::Issued,
                Money::compare($paid, $this->total) >= 0 => InvoiceStatus::Paid,
                default => InvoiceStatus::PartiallyPaid,
            };
        }

        $this->forceFill([
            'amount_paid' => $paid,
            'balance_due' => $balance,
            'status' => $status,
        ])->save();
    }

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'issued_on' => 'date',
            'due_on' => 'date',
            'issued_snapshot' => 'array',
        ];
    }
}
