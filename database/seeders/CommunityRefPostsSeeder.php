<?php

namespace Database\Seeders;

use App\Enums\CommunityContentType;
use App\Models\CommunityGroup;
use App\Models\CommunityPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Đưa **sự kiện** và **bình chọn** đã có vào feed cộng đồng dưới dạng bài
 * `*_ref`.
 *
 * Vì sao cần: hai tab "Sự kiện" và "Bình chọn" ở app lọc feed theo
 * `content_type` (`CommunityContentType::forTab()`), nhưng toàn bộ bài do các
 * seeder cũ tạo đều là `status`. Kết quả: DB có 9 sự kiện + 4 bình chọn mà hai
 * tab vẫn rỗng trơn — nhìn như app lỗi.
 *
 * Bài `*_ref` **chỉ tham chiếu** tới entity gốc (`source_type`/`source_id`),
 * không sao chép nội dung sang. Sửa tiêu đề sự kiện thì feed không lệch theo,
 * vì feed không giữ bản sao nào.
 *
 * Tác giả là BQL (`author_kind = 'staff'`): sự kiện và bình chọn do ban quản lý
 * tổ chức, không phải cư dân tự đăng.
 *
 * Idempotent qua `title = REF-EVENT-<id>` / `REF-POLL-<id>`; chạy lại không
 * nhân bản.
 *
 *   php artisan db:seed --class=CommunityRefPostsSeeder
 */
class CommunityRefPostsSeeder extends Seeder
{
    public function run(): void
    {
        // Nhóm "Cư dân dự án X" là chỗ đúng cho thông báo tổ chức: rộng hơn nhóm
        // riêng, nhưng vẫn chỉ cư dân đã xác thực của dự án đó thấy — không lọt
        // sang nhóm "Quan tâm" của người chưa mua nhà.
        $groups = CommunityGroup::withoutGlobalScopes()
            ->where('kind', 'project_resident')
            ->whereNotNull('project_id')
            ->get()
            ->keyBy('project_id');

        if ($groups->isEmpty()) {
            $this->command?->warn('  Chưa có nhóm project_resident — chạy CommunityGroupLadderSeeder trước.');

            return;
        }

        $events = $this->seedEvents($groups);
        $polls = $this->seedPolls($groups);

        $this->command?->info("  Feed ref: {$events} bài sự kiện + {$polls} bài bình chọn.");
    }

    /** @param Collection<int,CommunityGroup> $groups */
    private function seedEvents(Collection $groups): int
    {
        // CHỈ `published` — phải khớp đúng bộ lọc của `CommunityController::events()`.
        // Tạo bài ref cho sự kiện mà endpoint không trả về thì app không tra ra
        // entity gốc, và thẻ sự kiện rơi về chữ trơn: người dùng thấy một bài
        // "Sự kiện: ..." mà không có ngày, không có địa điểm, không bấm được.
        //
        // ⚠️ Dữ liệu hiện có event #1 mang `status = 'upcoming'` (seeder cũ) nên
        // KHÔNG cư dân nào xem được — cần owner chốt: `upcoming` là trạng thái
        // hợp lệ để hiện cho cư dân, hay chỉ là rác dữ liệu cần đổi sang
        // `published`? Chưa chốt thì không mở rộng bộ lọc ở endpoint.
        $rows = DB::table('events')
            ->whereNull('deleted_at')
            ->where('status', 'published')
            ->orderBy('starts_at')
            ->get();

        $n = 0;
        foreach ($rows as $e) {
            $group = $groups->get($e->project_id);
            if ($group === null) {
                continue;
            }

            $when = $e->starts_at ? Carbon::parse($e->starts_at)->format('d/m/Y H:i') : null;
            $body = 'Sự kiện: '.$e->title
                .($when ? ' — '.$when : '')
                .($e->location ? ' tại '.$e->location : '')
                .'. Xem chi tiết và đăng ký ở thẻ bên dưới.';

            CommunityPost::withoutGlobalScopes()->updateOrCreate(
                ['title' => 'REF-EVENT-'.$e->id],
                [
                    'tenant_id' => $e->tenant_id,
                    'project_id' => $e->project_id,
                    'community_group_id' => $group->id,
                    'content_type' => CommunityContentType::EventRef->value,
                    'source_type' => 'event',
                    'source_id' => $e->id,
                    'author_resident_id' => null,
                    'author_user_id' => null,
                    'author_kind' => 'staff',
                    'body' => $body,
                    'image_paths' => [],
                    'status' => 'published',
                    'published_at' => $e->created_at,
                    // Sự kiện gần nhất nổi lên đầu feed; các bài khác giữ nguyên
                    // thứ tự theo created_at nên không đè lên nhau.
                    'created_at' => $e->created_at,
                ]
            );
            $n++;
        }

        return $n;
    }

    /** @param Collection<int,CommunityGroup> $groups */
    private function seedPolls(Collection $groups): int
    {
        $rows = DB::table('polls')
            ->whereNull('deleted_at')
            ->where('status', 'open')
            ->orderByDesc('id')
            ->get();

        $n = 0;
        foreach ($rows as $p) {
            $group = $groups->get($p->project_id);
            if ($group === null) {
                continue;
            }

            $closes = $p->closes_at ? Carbon::parse($p->closes_at)->format('d/m/Y') : null;
            $body = 'Bình chọn: '.$p->question
                .($closes ? ' (đóng ngày '.$closes.')' : '')
                .'. Mời cư dân cho ý kiến.';

            CommunityPost::withoutGlobalScopes()->updateOrCreate(
                ['title' => 'REF-POLL-'.$p->id],
                [
                    'tenant_id' => $p->tenant_id,
                    'project_id' => $p->project_id,
                    'community_group_id' => $group->id,
                    'content_type' => CommunityContentType::PollRef->value,
                    'source_type' => 'poll',
                    'source_id' => $p->id,
                    'author_resident_id' => null,
                    'author_user_id' => null,
                    'author_kind' => 'staff',
                    'body' => $body,
                    'image_paths' => [],
                    'status' => 'published',
                    'published_at' => $p->created_at,
                    'created_at' => $p->created_at,
                ]
            );
            $n++;
        }

        return $n;
    }
}
