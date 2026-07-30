<?php

namespace App\Services\Resident;

use App\Enums\CommunityContentType;
use App\Models\CommunityGroup;
use App\Models\CommunityPost;
use App\Models\RealEstateListing;

/**
 * Sinh/gỡ bài `listing_ref` khi tin rao được DUYỆT/RÚT — mirror
 * `CommunityRefPostsSeeder` cho event/poll (nền tảng đã có, không dựng kiến
 * trúc mới). Dùng chung giữa [ListingController] (đường HTTP) và
 * `ListingDemoSeeder` (đường seed) để hai nơi không lệch logic.
 */
class ListingFeedPublisher
{
    /**
     * Khoá idempotent theo `source_type`+`source_id`: duyệt lại (hiếm khi) thì
     * cập nhật bài cũ, không nhân bản.
     */
    public function publish(RealEstateListing $listing): void
    {
        if ($listing->project_id === null) {
            return;
        }

        $group = CommunityGroup::withoutGlobalScope('tenant')
            ->where('project_id', $listing->project_id)
            ->where('kind', 'project_interest')
            ->first();
        // Dự án chưa seed bậc thang nhóm — bỏ qua, KHÔNG chặn việc duyệt tin.
        if ($group === null) {
            return;
        }

        $typeLabel = $listing->type === 'rent' ? 'Cho thuê' : 'Bán';
        $priceText = number_format((float) $listing->price, 0, ',', '.').' đ'
            .($listing->type === 'rent' ? '/tháng' : '');

        CommunityPost::withoutGlobalScope('tenant')->updateOrCreate(
            ['source_type' => 'listing', 'source_id' => $listing->id],
            [
                'tenant_id' => $listing->tenant_id,
                'project_id' => $listing->project_id,
                'community_group_id' => $group->id,
                'content_type' => CommunityContentType::ListingRef->value,
                'author_resident_id' => null,
                'author_user_id' => null,
                'author_kind' => 'staff',
                'body' => "Tin rao: {$typeLabel} — {$listing->title} ({$priceText}). Xem chi tiết ở thẻ bên dưới.",
                'image_paths' => [],
                'status' => 'published',
                'published_at' => $listing->approved_at,
            ],
        );
    }

    /** Tin bị rút/xoá thì thẻ trong feed cũng rút theo — không thì bấm vào ra 404. */
    public function unpublish(RealEstateListing $listing): void
    {
        CommunityPost::withoutGlobalScope('tenant')
            ->where('source_type', 'listing')
            ->where('source_id', $listing->id)
            ->get()
            ->each(fn ($p) => $p->delete());
    }
}
