<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 5 — Khảo sát/bình chọn cư dân. */
class Poll extends Model
{
    use BelongsToTenant, SoftDeletes;

    /** Phạm vi 1 phiếu (BQL-NOTI). resident = 1 phiếu/người; apartment = 1 phiếu/căn. */
    public const VOTE_SCOPES = ['resident', 'apartment'];

    /** Hiển thị kết quả. */
    public const RESULT_VISIBILITY = ['after_vote', 'after_close', 'public_after_close', 'admin_only'];

    protected $guarded = [];

    protected $casts = [
        'closes_at' => 'datetime',
        'opens_at' => 'datetime',
        'anonymous' => 'boolean',
        'allow_change_vote' => 'boolean',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }
}
