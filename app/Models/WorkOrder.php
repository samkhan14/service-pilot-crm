<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\Priority;
use App\Enums\WorkOrderStatus;
use Database\Factories\WorkOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'team_id', 'reference', 'customer_id', 'service_location_id', 'service_request_id',
    'estimate_id', 'created_by', 'status', 'priority', 'title', 'description',
    'internal_notes', 'customer_notes', 'service_address_line1', 'service_address_line2',
    'service_city', 'service_region', 'service_postal_code', 'service_country',
    'scheduled_start_at', 'scheduled_end_at', 'estimated_duration_minutes',
    'travel_buffer_minutes', 'started_at', 'completed_at', 'cancelled_at',
    'cancellation_reason', 'incomplete_reason', 'signoff_name', 'signed_off_at',
    'currency', 'subtotal', 'tax_amount', 'total', 'reschedule_history',
])]
class WorkOrder extends Model
{
    /** @use HasFactory<WorkOrderFactory> */
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
     * @return BelongsTo<Estimate, $this>
     */
    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    /**
     * @return HasMany<WorkOrderLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(WorkOrderLineItem::class)->orderBy('position');
    }

    /**
     * @return HasMany<WorkOrderAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(WorkOrderAssignment::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'work_order_assignments')
            ->withPivot(['is_lead', 'scheduled_start_at', 'scheduled_end_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<WorkOrderChecklistItem, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(WorkOrderChecklistItem::class)->orderBy('position');
    }

    /**
     * @return HasMany<WorkOrderEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(WorkOrderEvent::class)->latest('occurred_at');
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function formattedServiceAddress(): string
    {
        return collect([
            $this->service_address_line1,
            $this->service_address_line2,
            trim(($this->service_city ?? '').($this->service_region ? ', '.$this->service_region : '')),
            $this->service_postal_code,
            $this->service_country,
        ])->filter()->implode(', ');
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->assignments()->where('user_id', $user->id)->exists();
    }

    protected function casts(): array
    {
        return [
            'status' => WorkOrderStatus::class,
            'priority' => Priority::class,
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'signed_off_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'reschedule_history' => 'array',
        ];
    }
}
