<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'team_id', 'work_order_id', 'user_id', 'is_lead', 'scheduled_start_at', 'scheduled_end_at',
])]
class WorkOrderAssignment extends Model
{
    use BelongsToTeam;

    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'is_lead' => 'boolean',
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
        ];
    }
}
