<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Database\Factories\ServiceLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id', 'customer_id', 'label', 'address_line1', 'address_line2', 'city',
    'region', 'postal_code', 'country', 'access_notes', 'is_primary',
])]
class ServiceLocation extends Model
{
    /** @use HasFactory<ServiceLocationFactory> */
    use BelongsToTeam, HasFactory;

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function formattedAddress(): string
    {
        return collect([
            $this->address_line1,
            $this->address_line2,
            trim(($this->city ?? '').($this->region ? ', '.$this->region : '')),
            $this->postal_code,
            $this->country,
        ])->filter()->implode(', ');
    }
}
