<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\AuditLog;
use App\Models\Statement;
use App\Models\StatementApproval;
use App\Models\StatementPublishLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Duyệt & phát hành bảng kê — maker-checker (D1, Phase B2,
 * `docs/delivery/04_INITIAL_PHASE_PLAN.md`).
 *
 * Trước bản này: KHÔNG dòng code nào set `approval_status='published'` (kể cả
 * import B1 luôn dừng ở `pending`, đúng thiết kế). Đây là chỗ DUY NHẤT được
 * phép đưa một bảng kê từ `pending` → `approved` → `published` — cùng tinh
 * thần với `ResidentPaymentClaimReviewer`: service thuần, không phụ thuộc
 * `auth()`/tầng UI, để `MyWork`, `StatementList`, hoặc job nền sau này đều gọi
 * vào MỘT chỗ thay vì tự viết lại state machine (chính đó là ba đường vòng đã
 * xác minh trước bản này: `MyWork.php:338`, `StatementApprovalQueue::
 * transitionRuns()` không lọc trạng thái hợp lệ, và `/fila/payments` — đã đóng
 * riêng ở PaymentResource).
 *
 * Chặn tự duyệt (G9): người duyệt không được là người tạo bảng kê
 * (`created_by_user_id`). Bảng kê cũ (trước cột này tồn tại, `created_by_user_id
 * = null`) không bị chặn — không có dữ liệu để so sánh thì không giả định.
 */
class StatementApprovalService
{
    /** @throws InvalidArgumentException khi sai trạng thái hoặc tự duyệt. */
    public function approve(Statement $statement, User $approver, ?string $note = null): Statement
    {
        return DB::transaction(function () use ($statement, $approver, $note) {
            $fresh = Statement::whereKey($statement->id)->lockForUpdate()->firstOrFail();

            if ($fresh->approval_status !== Statement::APPROVAL_PENDING) {
                throw new InvalidArgumentException("Bảng kê {$fresh->code} đang ở trạng thái \"{$fresh->approval_status}\", không thể duyệt (chỉ duyệt được từ \"chờ duyệt\").");
            }

            if ($fresh->created_by_user_id !== null && (int) $fresh->created_by_user_id === (int) $approver->id) {
                throw new InvalidArgumentException('Không thể tự duyệt bảng kê do chính mình tạo — cần người khác duyệt.');
            }

            $fresh->update([
                'approval_status' => Statement::APPROVAL_APPROVED,
                'approved_by_user_id' => $approver->id,
                'approval_note' => $note,
            ]);

            StatementApproval::create([
                'tenant_id' => $fresh->tenant_id,
                'billing_period_id' => $fresh->billing_period_id,
                'statement_id' => $fresh->id,
                'approver_id' => $approver->id,
                'level' => 1,
                'status' => 'approved',
                'note' => $note,
                'decided_at' => now(),
            ]);

            $this->audit($fresh, $approver, 'billing.statement.approve', "Duyệt bảng kê {$fresh->code}".($note ? ": {$note}" : ''));

            return $fresh;
        });
    }

    /** @throws InvalidArgumentException khi sai trạng thái. */
    public function reject(Statement $statement, User $rejecter, string $reason): Statement
    {
        return DB::transaction(function () use ($statement, $rejecter, $reason) {
            $fresh = Statement::whereKey($statement->id)->lockForUpdate()->firstOrFail();

            if (! in_array($fresh->approval_status, [Statement::APPROVAL_PENDING, Statement::APPROVAL_APPROVED], true)) {
                throw new InvalidArgumentException("Bảng kê {$fresh->code} đang ở trạng thái \"{$fresh->approval_status}\", không thể từ chối.");
            }

            $fresh->update([
                'approval_status' => Statement::APPROVAL_REJECTED,
                'approved_by_user_id' => $rejecter->id,
                'approval_note' => $reason,
            ]);

            StatementApproval::create([
                'tenant_id' => $fresh->tenant_id,
                'billing_period_id' => $fresh->billing_period_id,
                'statement_id' => $fresh->id,
                'approver_id' => $rejecter->id,
                'level' => 1,
                'status' => 'rejected',
                'note' => $reason,
                'decided_at' => now(),
            ]);

            $this->audit($fresh, $rejecter, 'billing.statement.reject', "Từ chối bảng kê {$fresh->code}: {$reason}");

            return $fresh;
        });
    }

    /**
     * Phát hành — CHỈ từ `approved`. Đây là bước duy nhất cư dân bắt đầu thấy
     * được bảng kê (`Statement::scopeVisibleToResident`, D1).
     *
     * @throws InvalidArgumentException khi sai trạng thái.
     */
    public function publish(Statement $statement, User $publisher, ?string $note = null, string $channel = 'app'): Statement
    {
        return DB::transaction(function () use ($statement, $publisher, $note, $channel) {
            $fresh = Statement::whereKey($statement->id)->lockForUpdate()->firstOrFail();

            if ($fresh->approval_status !== Statement::APPROVAL_APPROVED) {
                throw new InvalidArgumentException("Bảng kê {$fresh->code} đang ở trạng thái \"{$fresh->approval_status}\", chỉ phát hành được từ \"đã duyệt\".");
            }

            $now = now();

            // Snapshot BẤT BIẾN (D15): chụp nội dung bảng kê tại thời điểm phát hành —
            // đây là bản gốc cư dân nhận, không đổi kể cả dòng phí bị sửa về sau.
            $builder = new StatementSnapshotBuilder;
            $snapshot = $builder->build($fresh);

            $fresh->update([
                'approval_status' => Statement::APPROVAL_PUBLISHED,
                'published_at' => $now,
                'issued_at' => $fresh->issued_at ?? $now,
                'snapshot' => $snapshot,
                'snapshot_checksum' => $builder->checksum($snapshot),
                'snapshot_at' => $now,
            ]);

            StatementPublishLog::create([
                'tenant_id' => $fresh->tenant_id,
                'billing_period_id' => $fresh->billing_period_id,
                'published_by_id' => $publisher->id,
                'channel' => $channel,
                'statements_count' => 1,
                'published_at' => $now,
                'note' => $note,
            ]);

            $this->audit($fresh, $publisher, 'billing.statement.publish', "Phát hành bảng kê {$fresh->code}".($note ? ": {$note}" : ''));

            return $fresh;
        });
    }

    private function audit(Statement $statement, User $actor, string $action, string $description): void
    {
        AuditLog::create([
            'tenant_id' => $statement->tenant_id,
            'building_id' => $statement->building_id,
            'user_id' => $actor->id,
            'actor_name' => $actor->name,
            'action' => $action,
            'subject_type' => Statement::class,
            'subject_id' => $statement->id,
            'description' => $description,
        ]);
    }
}
