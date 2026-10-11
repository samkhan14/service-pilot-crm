<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\Priority;
use App\Enums\ServiceRequestStatus;
use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'team_id', 'reference', 'customer_id', 'service_location_id', 'assigned_user_id',
    'status', 'priority', 'source', 'contact_name', 'contact_email', 'contact_phone',
    'subject', 'description', 'internal_notes', 'intake_fingerprint', 'converted_at',
])]
class ServiceRequest extends Model
{
    /** @use HasFactory<ServiceRequestFactory> */
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
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    protected function casts(): array
    {
        return [
            'status' => ServiceRequestStatus::class,
            'priority' => Priority::class,
            'converted_at' => 'datetime',
        ];
    }
}
