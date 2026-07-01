<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Tier 2 — Đăng ký khách (C12). Phát hành pass qua VisitorPass. */
class VisitorRegistration extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'expected_at' => 'datetime',
        'expected_leave_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function passes(): HasMany
    {
        return $this->hasMany(VisitorPass::class);
    }
}
