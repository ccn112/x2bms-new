<?php

namespace App\Http\Controllers\Api\V1\Resident\Concerns;

use App\Models\ListingInquiry;
use App\Models\RealEstateListing;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Gắn `my_interest` / `my_inquiry_kinds` / `can{}` lên MỖI tin rao trước khi
 * đưa vào [RealEstateListingResource] — gộp MỘT truy vấn cho cả trang, giống
 * cách `CommunityPostController` gộp `reactions`/`can` (tránh N+1 khi feed có
 * hàng chục tin).
 *
 * `can` do SERVER quyết — app chỉ vẽ nút theo cờ này, không tự suy từ
 * `approval_status`/`status` phía client.
 */
trait AttachesListingMeta
{
    /** @param Collection<int,RealEstateListing> $listings */
    private function attachListingMeta(Request $request, Collection $listings): void
    {
        if ($listings->isEmpty()) {
            return;
        }

        $user = $request->user();
        $ids = $listings->pluck('id');

        $myRows = ListingInquiry::query()
            ->where('user_id', $user->id)
            ->whereIn('real_estate_listing_id', $ids)
            ->get(['real_estate_listing_id', 'kind'])
            ->groupBy('real_estate_listing_id');

        $listings->each(function ($listing) use ($myRows, $user) {
            $mineKinds = $myRows->get($listing->id, collect())->pluck('kind')->unique()->values()->all();
            $isOwn = $listing->created_by_user_id !== null && $listing->created_by_user_id === $user->id;
            $visible = $listing->isPubliclyVisible();

            $listing->listing_meta = [
                'my_interest' => in_array(ListingInquiry::KIND_INTEREST, $mineKinds, true),
                'my_inquiry_kinds' => array_values(array_diff($mineKinds, [ListingInquiry::KIND_INTEREST])),
                'can' => [
                    // Không tự quan tâm/liên hệ tin của chính mình; tin chưa
                    // duyệt hoặc hết hiệu lực thì cũng không ai tương tác được
                    // (trừ chính chủ, người luôn thấy tin mình qua `mine`, nơi
                    // các cờ này không áp dụng vì không hiện nút quan tâm/liên hệ).
                    'interest' => ! $isOwn && $visible,
                    'inquire' => ! $isOwn && $visible,
                    'withdraw' => $isOwn && $listing->deleted_at === null,
                ],
            ];
        });
    }
}
