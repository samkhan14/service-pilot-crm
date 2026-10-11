<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id', 'legal_name', 'timezone', 'currency', 'tax_label', 'default_tax_rate',
    'working_hours_start', 'working_hours_end', 'next_request_number', 'next_estimate_number',
    'next_work_order_number', 'next_invoice_number', 'default_payment_terms_days', 'payment_instructions',
])]
class TeamSetting extends Model
{
    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    protected function casts(): array
    {
        return [
            'default_tax_rate' => 'decimal:3',
            'working_hours_start' => 'integer',
            'working_hours_end' => 'integer',
            'next_request_number' => 'integer',
            'next_estimate_number' => 'integer',
            'next_work_order_number' => 'integer',
            'next_invoice_number' => 'integer',
            'default_payment_terms_days' => 'integer',
        ];
    }

    public static function forTeam(Team $team): self
    {
        return static::query()->firstOrCreate(
            ['team_id' => $team->id],
            ['legal_name' => $team->name],
        );
    }
}
