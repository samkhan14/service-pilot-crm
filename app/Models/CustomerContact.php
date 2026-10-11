<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'customer_id', 'name', 'email', 'phone', 'role', 'is_primary'])]
class CustomerContact extends Model
{
    use BelongsToTeam;

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
}
