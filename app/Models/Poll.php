<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 5 — Khảo sát/bình chọn cư dân. */
class Poll extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['closes_at' => 'datetime'];

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }
}
