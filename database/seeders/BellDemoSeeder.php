<?php

namespace Database\Seeders;

use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\User;
use App\Services\Notifications\ActivityEmitter;
use Illuminate\Database\Seeder;

/**
 * Demo N0 — CHUÔNG hợp nhất. Sinh vài activity targeted cho hai TK test để
 * `GET /resident/bell` trả về (broadcast BQL + activity của tôi). Idempotent qua
 * group_key `demo:*`. PHỤ THUỘC: CommunityTestResidentsSeeder (2 TK đã có căn).
 */
class BellDemoSeeder extends Seeder
{
    private const ACCOUNTS = ['test.cudan1@x2bms.vn', 'test.cudan2@x2bms.vn'];

    public function run(): void
    {
        $emitter = app(ActivityEmitter::class);

        foreach (self::ACCOUNTS as $email) {
            $user = User::where('email', $email)->first();
            if ($user === null) {
                continue;
            }
            $rel = ResidentApartmentRelation::withoutGlobalScopes()
                ->whereIn('resident_id', Resident::withoutGlobalScopes()->where('user_id', $user->id)->pluck('id'))
                ->whereNotNull('apartment_id')->first();
            if ($rel === null) {
                $this->command?->warn("Bỏ qua {$email}: chưa có căn hộ.");

                continue;
            }
            $resident = Resident::withoutGlobalScopes()->find($rel->resident_id);
            $tenantId = (int) $resident->tenant_id;

            // 1) Nhắc việc: phiếu đã duyệt.
            $emitter->emit([
                'recipient_user_id' => $user->id, 'tenant_id' => $tenantId,
                'kind' => 'ticket_approved', 'title' => 'Phiếu đăng ký sửa chữa đã được BQL duyệt',
                'body' => 'BQL đã duyệt phiếu của bạn — bấm để xem chi tiết.',
                'entity_type' => 'ticket', 'entity_id' => 1001, 'action_key' => 'view_ticket',
                'group_key' => 'demo:ticket:'.$user->id,
            ]);

            // 2) Trả lời công nợ.
            $emitter->emit([
                'recipient_user_id' => $user->id, 'tenant_id' => $tenantId,
                'kind' => 'debt_reply', 'title' => 'Thắc mắc công nợ của bạn đã được trả lời',
                'entity_type' => 'statement', 'entity_id' => 1, 'action_key' => 'view_statement',
                'group_key' => 'demo:debt:'.$user->id,
            ]);

            // 3) Tương tác cộng đồng (coalesce sẵn 2 lượt).
            $emitter->emit([
                'recipient_user_id' => $user->id, 'tenant_id' => $tenantId,
                'kind' => 'reaction', 'title' => 'Có người thả cảm xúc bài viết của bạn',
                'entity_type' => 'community_post', 'entity_id' => 5, 'action_key' => 'view_post',
                'group_key' => 'demo:post5:reaction:'.$user->id,
            ]);

            $this->command?->info("Bell demo cho {$email}: 3 activity (phiếu duyệt · công nợ · cảm xúc).");
        }
    }
}
