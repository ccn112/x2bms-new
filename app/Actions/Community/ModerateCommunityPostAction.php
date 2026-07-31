<?php

declare(strict_types=1);

namespace App\Actions\Community;

use App\Models\AuditLog;
use App\Models\CommunityPost;
use App\Models\User;
use InvalidArgumentException;

/**
 * Hành động kiểm duyệt bài cộng đồng (hide|unhide|lock|unlock|delete|restore) —
 * TÁCH ra khỏi `CommunityPostController::moderate()` 2026-07-31 (Phase B6) để
 * `Pages/CommunityModeration.php` (web BQL) gọi CÙNG một chỗ với app cư dân
 * (`POST resident/community/posts/{id}/moderate`), không chép lại state machine
 * ở hai nơi. Xem `docs/COMMUNITY_WRITE_MODERATION_DESIGN.md` §1.
 *
 * Việc KIỂM QUYỀN (`CommunityModerationService::canModerate`) nằm ở caller —
 * action này chỉ lo đúng state machine + audit, không biết gì về HTTP/Livewire.
 */
class ModerateCommunityPostAction
{
    public const ACTIONS = ['hide', 'unhide', 'lock', 'unlock', 'delete', 'restore'];

    /** Hành động nào bắt buộc nhập lý do — cư dân sẽ nhìn thấy lý do này. */
    private const REQUIRES_REASON = ['hide', 'lock', 'delete'];

    /**
     * @throws InvalidArgumentException khi action không hợp lệ hoặc thiếu lý do bắt buộc.
     */
    public function execute(CommunityPost $post, string $action, ?string $reason, User $actor): CommunityPost
    {
        if (! in_array($action, self::ACTIONS, true)) {
            throw new InvalidArgumentException("Hành động kiểm duyệt không hợp lệ: {$action}");
        }

        $reason = trim((string) $reason);
        if (in_array($action, self::REQUIRES_REASON, true) && $reason === '') {
            throw new InvalidArgumentException('Cần nhập lý do — cư dân sẽ nhìn thấy lý do này.');
        }

        $now = now();
        match ($action) {
            'hide' => $post->forceFill([
                'status' => 'hidden',
                'moderated_at' => $now,
                'moderated_by_user_id' => $actor->id,
                'moderation_reason' => $reason,
            ])->save(),
            'unhide' => $post->forceFill([
                'status' => 'published',
                'moderated_at' => $now,
                'moderated_by_user_id' => $actor->id,
                'moderation_reason' => null,
            ])->save(),
            'lock' => $post->forceFill([
                'locked_at' => $now,
                'locked_by_user_id' => $actor->id,
                'moderation_reason' => $reason,
            ])->save(),
            'unlock' => $post->forceFill([
                'locked_at' => null,
                'locked_by_user_id' => null,
            ])->save(),
            'delete' => $this->softDeleteWithReason($post, $actor->id, $reason, $now),
            'restore' => $post->restore(),
        };

        $this->audit($post, $actor->id, $action, $reason);

        return $post->fresh() ?? $post;
    }

    private function softDeleteWithReason(CommunityPost $post, int $userId, string $reason, $now): void
    {
        $post->forceFill([
            'moderated_at' => $now,
            'moderated_by_user_id' => $userId,
            'moderation_reason' => $reason,
        ])->save();
        $post->delete();
    }

    /**
     * Kiểm duyệt là hành động có thể bị khiếu nại → phải truy vết được ai làm.
     * Ghi mềm: thiếu bảng audit thì cũng không được làm hỏng request.
     */
    private function audit(CommunityPost $post, int $userId, string $action, string $reason): void
    {
        try {
            AuditLog::create([
                'tenant_id' => $post->tenant_id,
                'user_id' => $userId,
                'auditable_type' => $post->getMorphClass(),
                'auditable_id' => $post->id,
                'event' => 'community.moderate.'.$action,
                'new_values' => ['action' => $action, 'reason' => $reason],
            ]);
        } catch (\Throwable) {
            // bỏ qua — không chặn nghiệp vụ vì log
        }
    }
}
