<?php

namespace Database\Seeders;

use App\Models\CommunityGroup;
use App\Models\CommunityPost;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Bậc thang nhóm cộng đồng cho cư dân demo — chốt 2026-07-29.
 *
 * Bốn nấc từ rộng tới hẹp. Seed cho **cả hai dự án** của tài khoản demo để đổi
 * căn hộ là thấy bậc thang đổi theo.
 *
 * Chạy: `php artisan db:seed --class=CommunityGroupLadderSeeder`
 * Idempotent.
 */
class CommunityGroupLadderSeeder extends Seeder
{
    /** [project_id => [tenant_id, tên gọn, resident_id để làm tác giả]] */
    private const PROJECTS = [
        1 => [1, 'Sunshine Garden', 1305],
        3 => [2, 'Đại Phúc Riverside', 1306],
    ];

    public function run(): void
    {
        $this->seedPlatformGroup();

        foreach (self::PROJECTS as $projectId => [$tenantId, $shortName, $residentId]) {
            $this->seedProjectGroups($projectId, $tenantId, $shortName, $residentId);
        }

        $this->linkPublicCatalog();
    }

    /**
     * Nấc 1 — Cộng đồng X2, mọi người thấy.
     *
     * `post_policy = staff`: mở cho cả hệ thống đăng thì thành bãi spam trong
     * tuần đầu, mà đội kiểm duyệt thì chưa có. Cư dân vẫn bình luận được.
     */
    private function seedPlatformGroup(): void
    {
        $group = CommunityGroup::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => 1, 'name' => 'Cộng đồng X2 Living'],
            [
                'project_id' => null,
                'kind' => 'platform',
                'post_policy' => 'staff',
                'is_default' => true,
                'description' => 'Tin tức và hoạt động chung của toàn hệ thống X2',
                'member_count' => 12480,
                'status' => 'active',
            ]
        );

        $this->post($group, 1, null, 1305, 'x2-welcome', true,
            'Chào mừng cư dân X2 Living! Đây là nơi cập nhật tin tức chung của toàn hệ thống — '
            .'tính năng mới, chương trình ưu đãi liên kết và hoạt động cộng đồng giữa các dự án.');
        $this->post($group, 1, null, 1305, 'x2-app', false,
            'Ứng dụng vừa bổ sung ví căn hộ và cảnh báo khẩn cấp. Cập nhật lên bản mới để dùng nhé!');

        $this->command?->info('  Nhóm nền tảng: Cộng đồng X2 Living (chỉ BQL đăng).');
    }

    /** Nấc 2–4 cho một dự án. */
    private function seedProjectGroups(int $projectId, int $tenantId, string $shortName, int $residentId): void
    {
        // Nấc 2 — khách QUAN TÂM dự án. Chỉ CĐT/BQL đăng, và nội dung KHÔNG
        // trộn với bảng tin cư dân: người chưa mua nhà đọc được cư dân phàn nàn
        // thang máy hỏng là mất khách (chủ dự án chốt 29/07).
        $interest = CommunityGroup::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenantId, 'project_id' => $projectId, 'kind' => 'project_interest'],
            [
                'name' => 'Quan tâm '.$shortName,
                'post_policy' => 'staff',
                'is_default' => true,
                'description' => 'Tiến độ, sự kiện và chính sách bán hàng của dự án',
                'member_count' => $projectId === 1 ? 3120 : 1840,
                'status' => 'active',
            ]
        );
        $this->post($interest, $tenantId, $projectId, $residentId, "int-$projectId-progress", true,
            "Tiến độ $shortName tháng 7: hoàn thiện cảnh quan khu trung tâm, bàn giao đợt 3 dự kiến quý IV.");
        $this->post($interest, $tenantId, $projectId, $residentId, "int-$projectId-openday", false,
            "Mời quý khách tham quan nhà mẫu $shortName cuối tuần này, có xe đưa đón từ trung tâm.");

        // Nấc 3 — cư dân ĐÃ XÁC THỰC. Đây là nhóm duy nhất cư dân đăng tự do
        // (hậu kiểm — đã chốt 27/07).
        $resident = CommunityGroup::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $tenantId, 'project_id' => $projectId, 'kind' => 'project_resident'],
            [
                'name' => 'Cư dân '.$shortName,
                'post_policy' => 'members',
                'is_default' => true,
                'description' => 'Bảng tin nội bộ của cư dân đã xác thực',
                'member_count' => $projectId === 1 ? 860 : 412,
                'status' => 'active',
            ]
        );

        // Gắn các bài cộng đồng đã seed trước đây vào đúng nhóm cư dân — trước
        // bản này chúng chỉ có project_id, không thuộc nhóm nào.
        CommunityPost::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->whereNull('community_group_id')
            ->update(['community_group_id' => $resident->id]);

        // Nấc 4 — nhóm riêng, phải được duyệt.
        $privates = $projectId === 1
            ? [['Cha mẹ Sunshine', 'Trao đổi về trường lớp, đưa đón, hoạt động cho bé', 318],
               ['Chạy bộ Sunshine', 'Nhóm chạy sáng quanh khu nội khu', 96]]
            : [['CLB Chèo thuyền Đại Phúc', 'Kayak, SUP và chèo thuyền truyền thống', 128],
               ['Vườn rau ven sông', 'Nhóm trồng rau sạch tại vườn cộng đồng', 73]];

        foreach ($privates as [$name, $desc, $members]) {
            CommunityGroup::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenantId, 'project_id' => $projectId, 'name' => $name],
                [
                    'kind' => 'private',
                    'post_policy' => 'members',
                    'is_default' => false,
                    'description' => $desc,
                    'member_count' => $members,
                    'status' => 'active',
                ]
            );
        }

        // Nhóm chủ đề đã seed từ trước (Chợ nội khu, Yêu bếp, Thể thao…) rơi
        // vào `kind` mặc định của migration là `project_resident`. Sai vai trò:
        // "Cư dân {dự án}" là bảng tin CHUNG ai cũng ở trong, còn mấy nhóm kia
        // là chủ đề tự chọn tham gia → phải là `private`.
        CommunityGroup::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->where('is_default', false)
            ->where('kind', 'project_resident')
            ->update(['kind' => 'private']);

        $this->command?->info("  Dự án $projectId ($shortName): quan tâm + cư dân + nhóm riêng.");
    }

    /**
     * Nối dự án vận hành với bản ghi danh mục công khai cùng tên.
     *
     * Không có khoá này thì "khách quan tâm X" và "cư dân X" là hai chữ X khác
     * nhau — khách mua nhà xong, thành cư dân, mà hệ thống không biết đó là
     * cùng một dự án, và bậc thang đứt ở nấc giữa.
     */
    private function linkPublicCatalog(): void
    {
        $linked = 0;
        foreach (Project::withoutGlobalScopes()->whereNull('public_project_id')->get() as $project) {
            $match = \DB::table('public_projects')->where('name', $project->name)->first(['id']);
            if ($match === null) {
                continue;
            }
            $project->forceFill(['public_project_id' => $match->id])->saveQuietly();
            $linked++;
        }

        $this->command?->info("  Nối danh mục công khai: $linked dự án (khớp theo tên).");
    }

    private function post(
        CommunityGroup $group,
        int $tenantId,
        ?int $projectId,
        int $residentId,
        string $key,
        bool $pinned,
        string $body,
    ): void {
        CommunityPost::withoutGlobalScopes()->updateOrCreate(
            ['title' => 'SEED-'.$key],
            [
                'tenant_id' => $tenantId,
                'project_id' => $projectId,
                'community_group_id' => $group->id,
                'author_resident_id' => $residentId,
                'body' => $body,
                'like_count' => $pinned ? 124 : 38,
                'comment_count' => $pinned ? 17 : 6,
                'is_pinned' => $pinned,
                'is_important' => false,
                'image_paths' => [],
                'status' => 'published',
                'created_at' => Carbon::parse('2026-07-26'),
            ]
        );
    }
}
