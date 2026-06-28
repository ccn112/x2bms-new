<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'status' => WorkOrderStatus::class,
        'due_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
