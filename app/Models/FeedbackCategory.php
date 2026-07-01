<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackCategory extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    public function feedbackRequests(): HasMany
    {
        return $this->hasMany(FeedbackRequest::class);
    }
}
