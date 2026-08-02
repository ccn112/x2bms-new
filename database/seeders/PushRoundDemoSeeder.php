<?php

namespace Database\Seeders;

use App\Enums\NotificationChannel;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityPostReaction;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo "MỘT VÒNG PUSH" — dựng sẵn dữ liệu để test đẩy thông báo từ server tới máy
 * cư dân test (Samsung = cudan2, iPhone = cudan1), gồm:
 *
 *  1) THÔNG BÁO BQL — mỗi KÊNH một cái ({@see NotificationChannel}: khẩn cấp, hoá
 *     đơn, kỹ thuật, an ninh, phản ánh, tiện ích, cộng đồng, thông báo BQL, hệ
 *     thống). Đều `published`, bật kênh `push` + `app`, audience = CĂN của cả hai
 *     TK test → vừa đẩy push vừa hiện trong danh sách thông báo in-app. Bắn thật
 *     bằng lệnh `php artisan push:demo-round`.
 *
 *  2) BÀI CỘNG ĐỒNG — hai bài do chính cudan1/cudan2 viết (author_user_id có thật
 *     để @mention được tác giả), có thả cảm xúc + bình luận chéo giữa hai người →
 *     feed không rỗng, số đếm bình luận > 0, và gõ `@` ra được người đã thích +
 *     đã bình luận (kiểm đúng phạm vi @mention đã chốt).
 *
 * Idempotent (firstOrCreate/updateOrCreate theo `code`/`title`). Cần các TK test
 * đã có (chạy CommunityTestResidentsSeeder trước) — nếu chưa thì cảnh báo & thoát.
 */
class PushRoundDemoSeeder extends Seeder
{
    /** Nhận diện thông báo demo để lệnh push:demo-round tìm & bắn lại. */
    public const CODE_PREFIX = 'DEMO-PUSH-';

    private const ACCOUNTS = ['test.cudan1@x2bms.vn', 'test.cudan2@x2bms.vn'];

    public function run(): void
    {
        $people = $this->resolvePeople();
        if (count($people) < 2) {
            $this->command?->warn('Thiếu TK test (cudan1/cudan2) — chạy CommunityTestResidentsSeeder trước.');

            return;
        }

        $ctx = $people[0]; // cùng dự án/toà nên lấy ngữ cảnh từ người đầu
        $this->seedNotifications($ctx, array_column($people, 'apartment_id'));
        $this->seedCommunity($people);

        $this->command?->info(sprintf(
            'Demo push sẵn sàng: %d thông báo (mỗi kênh 1) + 2 bài cộng đồng có tương tác chéo. '
            .'Bắn push: php artisan push:demo-round',
            count($this->channelCatalog()),
        ));
    }

    /**
     * @return array<int,array{user:User,resident:Resident,apartment_id:int,building_id:int,project_id:int,tenant_id:int}>
     */
    private function resolvePeople(): array
    {
        $out = [];
        foreach (self::ACCOUNTS as $email) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                continue;
            }
            $resident = Resident::withoutGlobalScopes()->where('user_id', $user->id)->first();
            if (! $resident) {
                continue;
            }
            $rel = ResidentApartmentRelation::withoutGlobalScopes()
                ->where('resident_id', $resident->id)
                ->whereNotNull('apartment_id')
                ->orderByDesc('is_primary')->orderBy('id')->first();
            if (! $rel) {
                continue;
            }
            $apartment = Apartment::withoutGlobalScopes()->find($rel->apartment_id);
            $building = Building::withoutGlobalScopes()->find($apartment->building_id);
            $out[] = [
                'user' => $user,
                'resident' => $resident,
                'apartment_id' => (int) $apartment->id,
                'building_id' => (int) $building->id,
                'project_id' => (int) $building->project_id,
                'tenant_id' => (int) $apartment->tenant_id,
            ];
        }

        return $out;
    }

    /** @param  array<int,int>  $apartmentIds  các căn cần nhắm (cả hai TK test) */
    private function seedNotifications(array $ctx, array $apartmentIds): void
    {
        foreach ($this->channelCatalog() as $c) {
            $notification = Notification::updateOrCreate(
                ['code' => self::CODE_PREFIX.$c['type']],
                [
                    'tenant_id' => $ctx['tenant_id'],
                    'owner_level' => 'project',
                    'project_id' => $ctx['project_id'],
                    'building_id' => $ctx['building_id'],
                    'type' => $c['type'],
                    'title' => $c['title'],
                    'summary' => $c['summary'],
                    'body' => $c['body'],
                    'priority' => $c['priority'],
                    'status' => 'published',
                    'published_at' => now(),
                ],
            );

            // Audience = từng căn của TK test (khớp cả push lẫn danh sách in-app,
            // vì visibleQuery cư dân chỉ nhận all|building|apartment).
            $notification->audiences()->delete();
            foreach (array_unique($apartmentIds) as $aptId) {
                $notification->audiences()->create(['scope_type' => 'apartment', 'scope_id' => $aptId]);
            }

            // Kênh gửi: push (để bắn FCM) + app (để hiện chuông in-app).
            foreach (['push', 'app'] as $channel) {
                $notification->channels()->updateOrCreate(['channel' => $channel], ['enabled' => true]);
            }
        }
    }

    /** @param  array<int,array{user:User,resident:Resident,project_id:int,tenant_id:int}>  $people */
    private function seedCommunity(array $people): void
    {
        [$p1, $p2] = [$people[0], $people[1]];

        $postA = $this->post($p1, 'DEMO-COMM-A',
            'Nhà mình vừa dọn về, chào cả nhà nhé! Có group cư dân nào vui thì kết nạp mình với ạ.');
        $postB = $this->post($p2, 'DEMO-COMM-B',
            'Tối nay sảnh cộng đồng có chiếu bóng, nhà nào rảnh xuống giao lưu cho vui nha!');

        // Tương tác CHÉO: mỗi người thích + bình luận bài của người kia → feed hai
        // bên đều có tương tác, và gõ @ trong bình luận ra được người đã thích/cmt.
        $this->reactAndComment($postA, $p2, 'Chào hàng xóm mới, nhà mình ở cùng tầng nè!');
        $this->reactAndComment($postB, $p1, 'Nghe hấp dẫn quá, tối mình xuống nhé!');
    }

    private function post(array $person, string $tag, string $body): CommunityPost
    {
        return CommunityPost::withoutGlobalScopes()->updateOrCreate(
            ['project_id' => $person['project_id'], 'title' => $tag],
            [
                'tenant_id' => $person['tenant_id'],
                'author_resident_id' => $person['resident']->id,
                'author_user_id' => $person['user']->id, // có thật → @mention tác giả được
                'author_kind' => 'resident',
                'body' => $body,
                'status' => 'published',
                'content_type' => 'status',
            ],
        );
    }

    private function reactAndComment(CommunityPost $post, array $actor, string $body): void
    {
        CommunityPostReaction::firstOrCreate(
            ['community_post_id' => $post->id, 'user_id' => $actor['user']->id],
            ['emoji' => CommunityPostReaction::CODES[0]],
        );

        $exists = CommunityComment::where('community_post_id', $post->id)
            ->where('user_id', $actor['user']->id)
            ->where('body', $body)
            ->exists();
        if (! $exists) {
            CommunityComment::create([
                'community_post_id' => $post->id,
                'tenant_id' => $actor['tenant_id'],
                'project_id' => $actor['project_id'],
                'user_id' => $actor['user']->id,
                'author_name' => $actor['user']->name,
                'author_kind' => 'resident',
                'is_staff' => false,
                'body' => $body,
                'status' => 'visible',
            ]);
        }

        // Số đếm bình luận hiển thị realtime đọc từ community_comments visible; cột
        // comment_count để đồng bộ cho chắc (một số chỗ cũ còn đọc).
        DB::table('community_posts')->where('id', $post->id)->update([
            'comment_count' => CommunityComment::where('community_post_id', $post->id)->where('status', 'visible')->count(),
        ]);
    }

    /** @return array<int,array{type:string,title:string,summary:string,body:string,priority:string}> */
    private function channelCatalog(): array
    {
        $copy = [
            'emergency' => ['Diễn tập PCCC toà nhà 15h chiều nay', 'Báo động thử — không hoảng loạn', 'BQL diễn tập phòng cháy chữa cháy lúc 15:00. Khi nghe còi, mời cư dân theo hướng dẫn thoát nạn. Đây là diễn tập.', 'urgent'],
            'billing' => ['Hoá đơn phí dịch vụ tháng 8 đã phát hành', 'Hạn thanh toán 15/08', 'Hoá đơn phí quản lý, gửi xe và nước tháng 8 đã lên app. Mời cư dân kiểm tra và thanh toán trước hạn.', 'normal'],
            'maintenance' => ['Bảo trì thang máy block A ngày mai', 'Tạm ngưng 8:00–11:00', 'Ngày mai thang máy số 2 block A bảo trì định kỳ, tạm ngưng 8:00–11:00. Mong cư dân thông cảm.', 'normal'],
            'security' => ['Nhắc giữ an ninh dịp lễ', 'Khoá cửa, cảnh giác người lạ', 'Dịp nghỉ lễ lượng khách ra vào tăng. Mời cư dân khoá kỹ cửa, không cho người lạ đi theo vào sảnh.', 'high'],
            'feedback' => ['BQL đã phản hồi phản ánh của bạn', 'Về tiếng ồn thi công', 'Cảm ơn phản ánh của cư dân về tiếng ồn. BQL đã làm việc với nhà thầu, giới hạn giờ thi công 8:00–18:00.', 'normal'],
            'amenity' => ['Đặt hồ bơi của bạn đã được duyệt', 'Khung 18:00 hôm nay', 'Yêu cầu đặt hồ bơi khung 18:00 hôm nay đã được duyệt. Mời bạn mang thẻ cư dân khi tới.', 'normal'],
            'community' => ['Sự kiện Trung thu cư dân cuối tuần', 'Đăng ký cho bé tại sảnh', 'Cuối tuần này BQL tổ chức Trung thu cho các bé tại sảnh cộng đồng. Mời các gia đình đăng ký tham gia.', 'normal'],
            'announcement' => ['Thông báo lịch phun khử khuẩn định kỳ', 'Thứ 7 tuần này', 'BQL phun khử khuẩn khu vực chung vào thứ 7. Mong cư dân hạn chế để đồ ở hành lang trong buổi sáng.', 'normal'],
            'system' => ['Cập nhật ứng dụng cư dân bản mới', 'Nhiều cải tiến cộng đồng & công nợ', 'Ứng dụng vừa cập nhật: xem công nợ theo dịch vụ, @nhắc tên trong bình luận, thẻ xem trước liên kết. Mời trải nghiệm.', 'low'],
        ];

        $out = [];
        foreach (NotificationChannel::cases() as $ch) {
            $c = $copy[$ch->value] ?? null;
            if ($c === null) {
                continue;
            }
            $out[] = ['type' => $ch->value, 'title' => $c[0], 'summary' => $c[1], 'body' => $c[2], 'priority' => $c[3]];
        }

        return $out;
    }
}
