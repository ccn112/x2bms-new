<?php

namespace App\Filament\Concerns;

use App\Models\AuditLog;
use App\Models\RealEstateListing;
use App\Services\Resident\ListingFeedPublisher;
use Illuminate\Support\Facades\DB;

/**
 * Logic DUYỆT/TỪ CHỐI/ĐẨY LÊN SA cho tin rao — dùng CHUNG giữa màn BQL
 * (`App\Filament\Pages\ListingApprovalQueue`, /admin) và màn SuperAdmin
 * (`App\Filament\Sa\Pages\ListingModerationCenter`, /sa) để hai nơi không
 * lệch quy tắc (một chỗ sửa, cả hai màn cùng đúng).
 *
 * Cố ý KHÔNG gọi `Notification::make()->send()` ở đây — trait này phải gọi
 * được từ PHPUnit thuần (xem `tests/Feature/ListingModerationTest.php`) mà
 * không cần dựng context Livewire/session. Notification hiển thị nằm ở tầng
 * gọi (Action closure trong từng Page).
 *
 * ## Vì sao KHÔNG khoá theo "đã đẩy lên SA"
 *
 * Chốt 2026-07-30 (bản sửa lại): SA phải duyệt được MỌI tin, kể cả tin BQL
 * chưa từng đụng tới (dự án không có người trực). Escalation chỉ là tín hiệu
 * ưu tiên hiển thị ở màn SA, không phải điều kiện hành động. Để tránh BQL và
 * SA duyệt/từ chối NGƯỢC NHAU cùng lúc, mọi thao tác ở đây khoá theo BẢN GHI
 * (`lockForUpdate` trong transaction) và tự bỏ qua (no-op) nếu bản ghi mới
 * nhất đã ở đúng trạng thái đích — quyết định luôn tuần tự hoá theo ai COMMIT
 * trước, không có trạng thái lưng chừng/mâu thuẫn.
 */
trait ModeratesRealEstateListings
{
    /**
     * Duyệt một tin. Idempotent: tin đã `approved` thì trả về nguyên trạng,
     * KHÔNG gọi lại `ListingFeedPublisher::publish` để không phát sinh audit
     * log/lượt "duyệt lại" giả — bản thân `publish()` cũng đã idempotent
     * (updateOrCreate theo source_type+source_id) nên dù có lọt qua đây hai
     * lần cũng không sinh bài `listing_ref` thứ hai.
     */
    public function approveListing(RealEstateListing $record): RealEstateListing
    {
        return DB::transaction(function () use ($record) {
            $fresh = RealEstateListing::withoutGlobalScope('tenant')
                ->whereKey($record->getKey())->lockForUpdate()->first();

            if ($fresh === null || $fresh->isApproved()) {
                return $fresh ?? $record;
            }

            $now = now();
            $fresh->forceFill([
                'approval_status' => RealEstateListing::APPROVAL_APPROVED,
                'approved_by_user_id' => auth()->id(),
                'approved_at' => $now,
                'rejection_reason' => null,
                'published_at' => $fresh->published_at ?? $now,
            ])->save();

            app(ListingFeedPublisher::class)->publish($fresh);
            $this->auditListing('listing.approve', $fresh, 'Duyệt tin rao: '.$fresh->title);

            return $fresh;
        });
    }

    /**
     * Từ chối một tin — BẮT BUỘC lý do (cư dân đọc được lý do này ở "Tin rao
     * của tôi"). Chặn ngay ở tầng logic bằng exception, KHÔNG chỉ dựa vào
     * `->required()` của form Filament, vì đây là luật nghiệp vụ không được
     * lách qua đường nào khác (kể cả gọi trait này trực tiếp từ chỗ khác).
     */
    public function rejectListing(RealEstateListing $record, string $reason): RealEstateListing
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('Cần nhập lý do từ chối — cư dân sẽ nhìn thấy lý do này.');
        }

        return DB::transaction(function () use ($record, $reason) {
            $fresh = RealEstateListing::withoutGlobalScope('tenant')
                ->whereKey($record->getKey())->lockForUpdate()->first();

            if ($fresh === null || $fresh->approval_status === RealEstateListing::APPROVAL_REJECTED) {
                return $fresh ?? $record;
            }

            // Tin đã từng duyệt (đã lên feed) mà giờ bị từ chối/thu hồi thì thẻ
            // trong feed cộng đồng phải rút theo — không thì bấm vào ra 404.
            $wasApproved = $fresh->isApproved();

            $fresh->forceFill([
                'approval_status' => RealEstateListing::APPROVAL_REJECTED,
                'approved_by_user_id' => auth()->id(),
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            if ($wasApproved) {
                app(ListingFeedPublisher::class)->unpublish($fresh);
            }

            $this->auditListing('listing.reject', $fresh, 'Từ chối tin rao: '.$reason);

            return $fresh;
        });
    }

    /**
     * BQL đẩy một tin lên SuperAdmin xét (dấu hiệu môi giới giả/giá bất
     * thường…). Chỉ áp dụng cho tin CÒN CHỜ duyệt — tin đã có quyết định thì
     * đẩy lên không còn ý nghĩa (muốn xét lại thì BQL/SA tự vào duyệt lại,
     * không cần cơ chế escalate riêng).
     */
    public function escalateListing(RealEstateListing $record, string $note): RealEstateListing
    {
        $note = trim($note);
        if ($note === '') {
            throw new \InvalidArgumentException('Cần nhập lý do đẩy lên SuperAdmin.');
        }

        return DB::transaction(function () use ($record, $note) {
            $fresh = RealEstateListing::withoutGlobalScope('tenant')
                ->whereKey($record->getKey())->lockForUpdate()->first();

            if ($fresh === null || $fresh->approval_status !== RealEstateListing::APPROVAL_PENDING) {
                return $fresh ?? $record;
            }

            $fresh->forceFill([
                'escalated_at' => now(),
                'escalated_by_user_id' => auth()->id(),
                'escalation_note' => $note,
            ])->save();

            $this->auditListing('listing.escalate', $fresh, 'Đẩy tin rao lên SuperAdmin: '.$note);

            return $fresh;
        });
    }

    protected function auditListing(string $action, RealEstateListing $listing, string $description): void
    {
        $user = auth()->user();

        // Ghi vết bằng schema audit_logs THẬT (tenant_id/building_id/user_id/
        // actor_name/action/subject_type/subject_id/description) — KHÔNG dùng
        // cột auditable_type/event/new_values như một chỗ khác trong codebase
        // đã lỡ dùng nhầm (những cột đó không tồn tại trong bảng thật nên ghi
        // luôn thất bại âm thầm ở đó); dùng đúng schema thật thì đây là hành
        // động duyệt/từ chối công khai — phải truy vết được thật sự.
        AuditLog::create([
            'tenant_id' => $listing->tenant_id,
            'building_id' => $user?->building_id,
            'user_id' => $user?->id,
            'actor_name' => $user?->name,
            'action' => $action,
            'subject_type' => 'RealEstateListing',
            'subject_id' => $listing->id,
            'description' => $description,
        ]);
    }
}
