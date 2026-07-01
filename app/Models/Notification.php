<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tier 2 — Thông báo, phân quyền 3 lớp (owner_level platform|tenant|project).
 * KHÔNG dùng BelongsToTenant (thông báo platform có tenant_id null); quyền
 * quản lý/xem do scopeVisibleTo()/canManageBy() quyết định. Đối tượng nhận qua
 * `audiences`.
 */
class Notification extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'is_pinned' => 'boolean',
        'publish_at' => 'datetime',
        'expires_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public const OWNER_LEVEL = ['platform' => 'Toàn hệ thống', 'tenant' => 'Công ty', 'project' => 'Dự án'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function audiences(): HasMany
    {
        return $this->hasMany(NotificationAudience::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(NotificationChannel::class);
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(NotificationDeliveryLog::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(NotificationRead::class);
    }

    /** Thông báo mà $user được quản lý/xem theo 3 lớp. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isPlatformAdmin()) {
            return $query;
        }
        $tid = $user->tenant_id;
        $pids = $user->accessibleProjectIds() ?: [];
        $isTenantOp = $user->isTenantOperator();

        return $query->where(function (Builder $q) use ($tid, $pids, $isTenantOp) {
            // Thông báo platform đã phát hành/hẹn giờ → cấp dưới thấy (đọc).
            $q->orWhere(fn (Builder $q) => $q->where('owner_level', 'platform')->whereIn('status', ['published', 'scheduled']));

            if ($isTenantOp && $tid) {
                $q->orWhere(fn (Builder $q) => $q->whereIn('owner_level', ['tenant', 'project'])->where('tenant_id', $tid));
            } else {
                if ($pids) {
                    $q->orWhere(fn (Builder $q) => $q->where('owner_level', 'project')->whereIn('project_id', $pids));
                }
                if ($tid) {
                    $q->orWhere(fn (Builder $q) => $q->where('owner_level', 'tenant')->where('tenant_id', $tid)->whereIn('status', ['published', 'scheduled']));
                }
            }
        });
    }

    public function canManageBy(User $user): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }
        if ($user->isTenantOperator()) {
            return in_array($this->owner_level, ['tenant', 'project'], true) && $this->tenant_id === $user->tenant_id;
        }

        return $this->owner_level === 'project' && in_array($this->project_id, $user->accessibleProjectIds() ?: [], true);
    }
}
