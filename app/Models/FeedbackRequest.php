<?php

namespace App\Models;

use App\Enums\FeedbackStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackRequest extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'status' => FeedbackStatus::class,
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FeedbackCategory::class, 'feedback_category_id');
    }
}
