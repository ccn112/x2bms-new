<?php

namespace Database\Seeders;

use App\Models\ListingPostingGrant;
use App\Models\Project;
use App\Models\RealEstateListing;
use App\Services\Resident\ListingFeedPublisher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Dữ liệu mẫu cho TIN RAO theo quy trình duyệt chốt 2026-07-30: mỗi dự án demo
 * có đủ BA trạng thái (chờ duyệt / đã duyệt / bị từ chối) để soi được trên app
 * ngay cả khi chưa bấm tạo tin thật từ client.
 *
 * Dự án 1 bật `listings_auto_approve` để có nơi soi luồng "duyệt tự động"; dự
 * án 3 giữ tắt (mặc định) để soi luồng "chờ BQL duyệt" — hai luồng khác nhau
 * chốt ở quyết định #2.
 *
 * Idempotent theo `code` (updateOrCreate). Chạy:
 *   php artisan db:seed --class=ListingDemoSeeder
 */
class ListingDemoSeeder extends Seeder
{
    /** Tài khoản demo (nguyenvananh@gmail.com) — user_id=6, xem PROGRESS_TRACKER. */
    private const DEMO_USER_ID = 6;

    /** [project_id => [tenant_id, apartment_id CHỦ căn đứng tên, owner_resident_id, tên gọn]] */
    private const PROJECTS = [
        1 => [1, 11, 1305, 'Sunshine Garden'],
        3 => [2, 1305, 1306, 'Đại Phúc Riverside'],
    ];

    public function __construct(private readonly ListingFeedPublisher $feed) {}

    public function run(): void
    {
        Project::withoutGlobalScope('tenant')->whereKey(1)->update(['listings_auto_approve' => true]);
        Project::withoutGlobalScope('tenant')->whereKey(3)->update(['listings_auto_approve' => false]);

        $created = 0;
        foreach (self::PROJECTS as $projectId => [$tenantId, $apartmentId, $ownerResidentId, $shortName]) {
            $created += $this->seedForProject($projectId, $tenantId, $apartmentId, $ownerResidentId, $shortName);
        }

        // Quyền rao đã XÁC MINH cho người KHÔNG phải chủ căn — resident #11 giữ
        // `role=tenant` tại apartment #11 (cùng căn với chủ demo #1305), minh
        // hoạ cơ chế BQL cấp quyền rao hộ (quyết định #1).
        ListingPostingGrant::query()->updateOrCreate(
            ['apartment_id' => 11, 'resident_id' => 11],
            [
                'tenant_id' => 1,
                'granted_by_user_id' => null,
                'status' => ListingPostingGrant::STATUS_ACTIVE,
                'note' => 'Người thuê được chủ nhà uỷ quyền rao tin — demo BQL cấp quyền.',
            ],
        );

        $this->command?->info("  Listings demo: {$created} tin (3 trạng thái duyệt + 1 tin của người khác) × 2 dự án + 1 quyền rao đã xác minh.");
    }

    private function seedForProject(
        int $projectId,
        int $tenantId,
        int $apartmentId,
        int $ownerResidentId,
        string $shortName,
    ): int {
        $rows = [
            [
                'code' => "RE-DEMO-P{$projectId}-APPROVED",
                'type' => 'sale',
                'title' => "Bán căn 2PN, view đẹp — {$shortName}",
                'price' => 3_600_000_000, 'area' => 65, 'bed' => 2,
                'approval_status' => 'approved',
            ],
            [
                'code' => "RE-DEMO-P{$projectId}-PENDING",
                'type' => 'rent',
                'title' => "Cho thuê 1PN đầy đủ nội thất — {$shortName}",
                'price' => 9_500_000, 'area' => 40, 'bed' => 1,
                'approval_status' => 'pending',
            ],
            [
                'code' => "RE-DEMO-P{$projectId}-REJECTED",
                'type' => 'sale',
                'title' => "Bán gấp căn góc, sổ hồng riêng — {$shortName}",
                'price' => 4_200_000_000, 'area' => 78, 'bed' => 3,
                'approval_status' => 'rejected',
            ],
            [
                // Tin của NGƯỜI KHÁC (created_by = user #1, không phải tài
                // khoản demo) — cần thiết để verify HTTP quan tâm/để lại thông
                // tin: tài khoản demo không thể tự quan tâm/liên hệ tin CHÍNH
                // MÌNH vừa đăng (chặn ở `findInteractable`), nên phải có ít
                // nhất một tin "của người khác" trong cùng dự án để bấm thử.
                'code' => "RE-DEMO-P{$projectId}-APPROVED-OTHER",
                'type' => 'rent',
                'title' => "Cho thuê studio giá tốt — {$shortName}",
                'price' => 7_000_000, 'area' => 30, 'bed' => 1,
                'approval_status' => 'approved',
                'created_by_user_id' => 1,
            ],
        ];

        $now = Carbon::parse('2026-07-29 09:00');
        $n = 0;
        foreach ($rows as $r) {
            $approved = $r['approval_status'] === 'approved';

            $listing = RealEstateListing::withoutGlobalScope('tenant')->updateOrCreate(
                ['code' => $r['code']],
                [
                    'tenant_id' => $tenantId,
                    'project_id' => $projectId,
                    'apartment_id' => $apartmentId,
                    'owner_resident_id' => $ownerResidentId,
                    'created_by_user_id' => $r['created_by_user_id'] ?? self::DEMO_USER_ID,
                    'type' => $r['type'],
                    'title' => $r['title'],
                    'price' => $r['price'],
                    'area' => $r['area'],
                    'bedrooms' => $r['bed'],
                    'status' => 'active',
                    'approval_status' => $r['approval_status'],
                    'approved_by_user_id' => $approved ? self::DEMO_USER_ID : null,
                    'approved_at' => $approved ? $now : null,
                    'rejection_reason' => $r['approval_status'] === 'rejected'
                        ? 'Thiếu ảnh thực tế căn hộ — vui lòng bổ sung ảnh rồi đăng lại.'
                        : null,
                    'published_at' => $approved ? $now : null,
                ],
            );

            if ($approved) {
                $this->feed->publish($listing);
            }
            $n++;
        }

        return $n;
    }
}
