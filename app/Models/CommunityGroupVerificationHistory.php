<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lịch sử đổi mức xác minh của nhóm (none → platform_verified → bql_official).
 * Nâng cấp gold→blue (docs 09 Stage 5) phải GIỮ NGUYÊN group id/thành viên/bài
 * viết — chỉ ghi lại mốc đổi mức + ai đổi + vì sao.
 */
class CommunityGroupVerificationHistory extends Model
{
    protected $guarded = [];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CommunityGroup::class, 'community_group_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
