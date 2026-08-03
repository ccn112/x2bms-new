<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\ActivityNotification;

/**
 * Sinh một thông báo hoạt động (bell targeted) cho MỘT người nhận (N0).
 *
 * Nguồn gọi: handler của domain event (phiếu duyệt, trả lời công nợ, bình luận…),
 * và fan-out broadcast (kind=announcement). Đây là đường DUY NHẤT ghi
 * `activity_notifications` — để coalesce + (sau này) đẩy kênh nằm một chỗ.
 *
 * Coalesce (ADR-001 mục 3): truyền `group_key` (vd "post:123:reaction") → nhiều
 * tương tác cùng entity GỘP một dòng: tăng `coalesce_count`, cập nhật actor mới nhất,
 * đưa `read_at` về null để nổi lại lên đầu chuông. Không có group_key → luôn tạo dòng mới.
 */
class ActivityEmitter
{
    /**
     * @param  array{
     *   recipient_user_id:int, tenant_id:int, kind:string,
     *   title:string, project_id?:?int, actor_user_id?:?int, subtype?:?string,
     *   body?:?string, image_url?:?string, entity_type?:?string, entity_id?:?int,
     *   action_key?:?string, announcement_id?:?int, group_key?:?string
     * }  $data
     */
    public function emit(array $data): ActivityNotification
    {
        $recipient = (int) $data['recipient_user_id'];
        $groupKey = $data['group_key'] ?? null;

        if ($groupKey !== null && $groupKey !== '') {
            $existing = ActivityNotification::query()
                ->where('recipient_user_id', $recipient)
                ->where('group_key', $groupKey)
                ->first();

            if ($existing !== null) {
                $existing->forceFill([
                    'actor_user_id' => $data['actor_user_id'] ?? $existing->actor_user_id,
                    'title' => $data['title'],
                    'body' => $data['body'] ?? $existing->body,
                    'image_url' => $data['image_url'] ?? $existing->image_url,
                    'coalesce_count' => $existing->coalesce_count + 1,
                    'read_at' => null,          // nổi lại lên đầu (chưa đọc)
                ])->save();
                $existing->touch();             // created ordering: đẩy lên mới nhất

                return $existing;
            }
        }

        return ActivityNotification::create([
            'tenant_id' => $data['tenant_id'],
            'project_id' => $data['project_id'] ?? null,
            'recipient_user_id' => $recipient,
            'actor_user_id' => $data['actor_user_id'] ?? null,
            'kind' => $data['kind'],
            'subtype' => $data['subtype'] ?? null,
            'title' => $data['title'],
            'body' => $data['body'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'action_key' => $data['action_key'] ?? null,
            'announcement_id' => $data['announcement_id'] ?? null,
            'group_key' => $groupKey,
            'coalesce_count' => 1,
        ]);
    }
}
