<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\CustomerStatus;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property string $name
 * @property CustomerStatus $status
 */
#[Fillable([
    'team_id', 'name', 'company_name', 'email', 'phone', 'alternate_phone', 'status', 'tags',
    'communication_preference', 'internal_notes', 'billing_address_line1', 'billing_address_line2',
    'billing_city', 'billing_region', 'billing_postal_code', 'billing_country', 'archived_at',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use BelongsToTeam, HasFactory;

    /**
     * @return HasMany<ServiceLocation, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(ServiceLocation::class);
    }

    /**
     * @return HasMany<CustomerContact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    /**
     * @return HasMany<ServiceRequest, $this>
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * @return HasMany<WorkOrder, $this>
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    /**
     * @return HasMany<Estimate, $this>
     */
    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    protected function casts(): array
    {
        return [
            'status' => CustomerStatus::class,
            'tags' => 'array',
            'archived_at' => 'datetime',
        ];
    }
}
