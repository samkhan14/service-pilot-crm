<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\EstimateStatus;
use Database\Factories\EstimateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'team_id', 'reference', 'customer_id', 'service_location_id', 'service_request_id',
    'created_by', 'status', 'version', 'currency', 'subtotal', 'discount_amount',
    'tax_rate', 'tax_amount', 'total', 'issued_on', 'expires_on', 'sent_at',
    'approved_at', 'approved_by_name', 'rejected_at', 'notes', 'customer_notes',
    'accepted_snapshot',
])]
class Estimate extends Model
{
    /** @use HasFactory<EstimateFactory> */
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
     * @return BelongsTo<ServiceLocation, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(ServiceLocation::class, 'service_location_id');
    }

    /**
     * @return BelongsTo<ServiceRequest, $this>
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * @return HasMany<EstimateLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(EstimateLineItem::class)->orderBy('position');
    }

    /**
     * @return HasMany<WorkOrder, $this>
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    protected function casts(): array
    {
        return [
            'status' => EstimateStatus::class,
            'version' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:3',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'issued_on' => 'date',
            'expires_on' => 'date',
            'sent_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'accepted_snapshot' => 'array',
        ];
    }
}
