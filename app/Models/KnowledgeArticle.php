<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WEB-UX-09-04 · Bài viết cơ sở tri thức (KB).
 *
 * Sở hữu 3 cấp (owner_level): platform | tenant | project — khớp RBAC 3 tầng.
 * Chia sẻ (share_mode): private (chỉ chủ) | descendants (mọi cấp dưới) | custom
 * (theo `knowledge_article_shares`). Hiển thị theo `scopeVisibleTo()`.
 *
 * KHÔNG dùng BelongsToTenant (global scope tenant_id) vì tài liệu platform có
 * tenant_id null và có thể chia sẻ xuyên tenant — quyền xem do visibleTo() quyết định.
 */
class KnowledgeArticle extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
        'attachments' => 'array',
    ];

    public const OWNER_LEVEL = ['platform' => 'Toàn hệ thống', 'tenant' => 'Công ty', 'project' => 'Dự án'];

    public const SHARE_MODE = ['private' => 'Riêng', 'descendants' => 'Chia sẻ cấp dưới', 'custom' => 'Chia sẻ tùy chọn'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'knowledge_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(KnowledgeArticleShare::class);
    }

    /**
     * Tài liệu mà $user được PHÉP XEM theo mô hình 3 cấp:
     *  - Platform admin: tất cả.
     *  - Công ty (tenant operator): mọi tài liệu công ty + dự án trong tenant,
     *    cộng tài liệu platform chia sẻ xuống.
     *  - BQL (project): tài liệu dự án mình + tài liệu công ty/platform chia sẻ xuống.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isPlatformAdmin()) {
            return $query;
        }

        $tid = $user->tenant_id;
        $pids = $user->accessibleProjectIds() ?: [];
        $isTenantOp = $user->isTenantOperator();

        // Có 1 dòng chia sẻ tường minh tới tenant hoặc project của user.
        $customShare = function ($s) use ($tid, $pids) {
            $s->where(function ($s) use ($tid) {
                $tid ? $s->where('scope_type', 'tenant')->where('scope_id', $tid) : $s->whereRaw('1 = 0');
            })->orWhere(function ($s) use ($pids) {
                $pids ? $s->where('scope_type', 'project')->whereIn('scope_id', $pids) : $s->whereRaw('1 = 0');
            });
        };
        $sharedDown = function (Builder $q) use ($customShare) {
            $q->where('share_mode', 'descendants')
                ->orWhere(fn (Builder $q) => $q->where('share_mode', 'custom')->whereHas('shares', $customShare));
        };

        return $query->where(function (Builder $q) use ($tid, $pids, $isTenantOp, $sharedDown) {
            // 1) Tài liệu platform được chia sẻ xuống
            $q->orWhere(fn (Builder $q) => $q->where('owner_level', 'platform')->where($sharedDown));

            if ($isTenantOp && $tid) {
                // 2a) Công ty: mọi tài liệu công ty + dự án trong tenant
                $q->orWhere(fn (Builder $q) => $q->whereIn('owner_level', ['tenant', 'project'])->where('tenant_id', $tid));
            } else {
                // 2b) BQL: tài liệu dự án của mình
                if ($pids) {
                    $q->orWhere(fn (Builder $q) => $q->where('owner_level', 'project')->whereIn('project_id', $pids));
                }
                // + tài liệu công ty được chia sẻ xuống dự án của mình
                if ($tid) {
                    $q->orWhere(fn (Builder $q) => $q->where('owner_level', 'tenant')->where('tenant_id', $tid)->where($sharedDown));
                }
            }
        });
    }

    /** $user có được QUẢN LÝ (sửa/chia sẻ) bài này không? */
    public function canManageBy(User $user): bool
    {
        if ($user->isPlatformAdmin()) {
            return true;
        }
        // Công ty: quản lý tài liệu công ty + dự án trong tenant của mình.
        if ($user->isTenantOperator()) {
            return in_array($this->owner_level, ['tenant', 'project'], true) && $this->tenant_id === $user->tenant_id;
        }
        // BQL: chỉ tài liệu dự án của mình.
        return $this->owner_level === 'project' && in_array($this->project_id, $user->accessibleProjectIds() ?: [], true);
    }
}
