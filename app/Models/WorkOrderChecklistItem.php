<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['work_order_id', 'label', 'is_completed', 'position'])]
class WorkOrderChecklistItem extends Model
{
    /**
     * @return BelongsTo<WorkOrder, $this>
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
        ];
    }
}
