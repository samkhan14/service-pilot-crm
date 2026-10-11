<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['work_order_id', 'position', 'description', 'quantity', 'unit_price', 'tax_rate', 'line_total', 'is_material'])]
class WorkOrderLineItem extends Model
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
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:3',
            'line_total' => 'decimal:2',
            'is_material' => 'boolean',
        ];
    }
}
