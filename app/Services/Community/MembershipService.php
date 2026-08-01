<?php

namespace App\Services\Community;

use App\Enums\CommunityGroupType;
use App\Models\Apartment;
use App\Models\CommunityGroup;
use App\Models\CommunityGroupMember;
use App\Models\CommunityMembershipGrant;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Giai đoạn 3 Community Domain (2026-08-01) — `COMMUNITY_DB_MAPPING.md` §3,
 * `COMMUNITY_RISK_ROLLBACK.md` R2.
 *
 * MỘT chỗ duy nhất cấp/thu hồi thành viên nhóm cộng đồng qua grant. Quy tắc
 * nghiệp vụ CỐT LÕI (COM-007, locked 2026-07-29): một membership có thể tồn
 * tại nhờ NHIỀU grant (hai căn hộ khác nhau, hoặc một quan hệ căn hộ + một
 * lần tự tham gia) — membership chỉ thật sự bị thu hồi khi KHÔNG còn grant
 * `active` nào. Mất một quan hệ căn hộ không được kéo theo mất quyền ở nhóm
 * mà người đó còn giữ quyền qua đường khác.
 *
 * Không gọi thẳng `CommunityGroupMember::create()`/`delete()` ở nơi khác —
 * mọi đường cấp/thu hồi phải qua service này để bất biến "còn active grant
 * thì còn membership" luôn đúng.
 */
class MembershipService
{
    /**
     * Cấp một grant cho membership (group, resident|user). Idempotent: gọi lại
     * với cùng (group, resident/user, source_type, source_id) không tạo trùng,
     * và "hồi sinh" grant đã revoked trước đó thay vì tạo dòng mới.
     */
    public function grant(
        CommunityGroup $group,
        string $sourceType,
        ?int $sourceId,
        ?int $residentId = null,
        ?int $userId = null,
        ?int $grantedByUserId = null,
    ): CommunityGroupMember {
        if ($residentId === null && $userId === null) {
            throw new InvalidArgumentException('MembershipService::grant() cần resident_id hoặc user_id.');
        }

        return DB::transaction(function () use ($group, $sourceType, $sourceId, $residentId, $userId, $grantedByUserId) {
            $match = ['community_group_id' => $group->id];
            $match += $residentId !== null ? ['resident_id' => $residentId] : ['user_id' => $userId];

            /** @var CommunityGroupMember $member */
            $member = CommunityGroupMember::query()->where($match)->lockForUpdate()->first()
                ?? new CommunityGroupMember($match + ['role' => 'member']);

            $isNew = ! $member->exists;
            $wasLeft = $member->exists && $member->left_at !== null;

            if ($isNew) {
                $member->joined_at = now();
            }
            if ($isNew || $wasLeft) {
                $member->left_at = null;
            }
            // Điền thêm cạnh còn thiếu (vd membership tạo trước qua nhánh
            // resident_id giờ cũng có user_id, hoặc ngược lại) — không ghi đè
            // giá trị đã có.
            if ($userId !== null && $member->user_id === null) {
                $member->user_id = $userId;
            }
            if ($residentId !== null && $member->resident_id === null) {
                $member->resident_id = $residentId;
            }
            $member->save();

            if ($isNew || $wasLeft) {
                $group->increment('member_count');
            }

            /** @var CommunityMembershipGrant $grant */
            $grant = CommunityMembershipGrant::query()
                ->where('membership_id', $member->id)
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->lockForUpdate()
                ->first();

            if ($grant === null) {
                $grant = CommunityMembershipGrant::create([
                    'membership_id' => $member->id,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'granted_by_user_id' => $grantedByUserId,
                    'status' => CommunityMembershipGrant::STATUS_ACTIVE,
                    'granted_at' => now(),
                ]);
            } elseif ($grant->status !== CommunityMembershipGrant::STATUS_ACTIVE) {
                $grant->update([
                    'status' => CommunityMembershipGrant::STATUS_ACTIVE,
                    'granted_at' => now(),
                    'revoked_at' => null,
                    'granted_by_user_id' => $grantedByUserId ?? $grant->granted_by_user_id,
                ]);
            }

            return $member->fresh();
        });
    }

    /**
     * Thu hồi ĐÚNG một grant. Membership chỉ chuyển sang "đã rời" (`left_at`)
     * khi đây là grant active CUỐI CÙNG của nó — nếu còn grant active khác thì
     * membership giữ nguyên, không đổi gì cả (đây là điều R2 yêu cầu test).
     *
     * Idempotent: gọi lại khi grant đã revoked hoặc không tồn tại là no-op.
     */
    public function revoke(
        CommunityGroup $group,
        string $sourceType,
        ?int $sourceId,
        ?int $residentId = null,
        ?int $userId = null,
    ): void {
        if ($residentId === null && $userId === null) {
            throw new InvalidArgumentException('MembershipService::revoke() cần resident_id hoặc user_id.');
        }

        DB::transaction(function () use ($group, $sourceType, $sourceId, $residentId, $userId) {
            $match = ['community_group_id' => $group->id];
            $match += $residentId !== null ? ['resident_id' => $residentId] : ['user_id' => $userId];

            $member = CommunityGroupMember::query()->where($match)->lockForUpdate()->first();
            if ($member === null) {
                return; // Chưa từng là thành viên — không có gì để thu hồi.
            }

            $grant = CommunityMembershipGrant::query()
                ->where('membership_id', $member->id)
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('status', CommunityMembershipGrant::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if ($grant === null) {
                return; // Grant này đã revoked hoặc chưa từng cấp — idempotent.
            }

            $grant->update(['status' => CommunityMembershipGrant::STATUS_REVOKED, 'revoked_at' => now()]);

            $stillHasActiveGrant = CommunityMembershipGrant::query()
                ->where('membership_id', $member->id)
                ->where('status', CommunityMembershipGrant::STATUS_ACTIVE)
                ->exists();

            if (! $stillHasActiveGrant && $member->left_at === null) {
                $member->left_at = now();
                $member->save();
                if ($group->member_count > 0) {
                    $group->decrement('member_count');
                }
            }
        });
    }

    /**
     * Quan hệ resident↔apartment vừa kích hoạt (Giai đoạn 3 mục 4) → cấp grant
     * vào nhóm cư dân CHÍNH THỨC (`official_resident_group`) của dự án chứa
     * căn hộ đó. Không tìm thấy nhóm chính thức nào (dự án chưa có) → no-op,
     * trả `null` — KHÔNG tự tạo nhóm (ngoài phạm vi service này).
     */
    public function grantResidentRelation(ResidentApartmentRelation $relation): ?CommunityGroupMember
    {
        $group = $this->officialGroupForRelation($relation);
        if ($group === null) {
            return null;
        }

        // withoutGlobalScopes() có chủ ý: hàm này chạy từ ngữ cảnh staff BQL
        // (`ResidentApprovalQueue::approve()`), nơi `Resident`/`Apartment` có
        // thể bị `BelongsToProject` lọc theo `accessibleProjectIds()` của
        // nhân sự đang đăng nhập — không liên quan gì tới việc resident/quan
        // hệ này có thật hay không.
        $resident = Resident::withoutGlobalScopes()->find($relation->resident_id);
        if ($resident === null) {
            return null;
        }

        return $this->grant(
            $group,
            CommunityMembershipGrant::SOURCE_RESIDENT_RELATION,
            $relation->id,
            residentId: $resident->id,
            userId: $resident->user_id,
        );
    }

    /**
     * Quan hệ resident↔apartment hết hiệu lực (Giai đoạn 3 mục 4) → thu hồi
     * ĐÚNG grant sinh ra từ quan hệ đó. Người này còn quan hệ khác (căn hộ
     * khác cùng dự án, hoặc dự án khác) thì KHÔNG bị ảnh hưởng — đó là grant
     * khác, `source_id` khác.
     */
    public function revokeResidentRelation(ResidentApartmentRelation $relation): void
    {
        $group = $this->officialGroupForRelation($relation);
        if ($group === null) {
            return;
        }

        $this->revoke(
            $group,
            CommunityMembershipGrant::SOURCE_RESIDENT_RELATION,
            $relation->id,
            residentId: $relation->resident_id,
        );
    }

    /** Nhóm cư dân chính thức của dự án chứa căn hộ trong quan hệ này, nếu có. */
    private function officialGroupForRelation(ResidentApartmentRelation $relation): ?CommunityGroup
    {
        $apartment = Apartment::withoutGlobalScopes()->find($relation->apartment_id);
        $projectId = $apartment === null
            ? null
            : \App\Models\Building::withoutGlobalScopes()->whereKey($apartment->building_id)->value('project_id');
        if ($projectId === null) {
            return null;
        }

        return CommunityGroup::withoutGlobalScopes()
            ->where('tenant_id', $relation->tenant_id)
            ->where('group_type', CommunityGroupType::OfficialResidentGroup->value)
            ->where('scope_type', 'project')
            ->where('scope_id', $projectId)
            ->first();
    }

    /** Cư dân tự tham gia một nhóm mở (`resident_custom_group`/`resident_interest_group`). */
    public function grantManualJoin(CommunityGroup $group, Resident $resident): CommunityGroupMember
    {
        return $this->grant(
            $group,
            CommunityMembershipGrant::SOURCE_MANUAL_JOIN,
            null,
            residentId: $resident->id,
            userId: $resident->user_id,
        );
    }

    /** Cư dân tự rời một nhóm đã tham gia thủ công. */
    public function revokeManualJoin(CommunityGroup $group, Resident $resident): void
    {
        $this->revoke($group, CommunityMembershipGrant::SOURCE_MANUAL_JOIN, null, residentId: $resident->id);
    }

    /**
     * Auto-enroll X2Living (Giai đoạn 3 mục 5, COM-002) — MỌI tài khoản đã
     * đăng nhập (tier `member`, không cần hồ sơ Resident) đều là thành viên
     * `platform_community`. Gọi tại `GET me/bootstrap` (xem
     * `BootstrapController::me()`): đây là lệnh gọi đầu tiên mọi phiên app đều
     * chạm tới sau khi đăng nhập, nên là điểm chắc chắn nhất để enroll mà
     * không cần thêm hook riêng ở luồng đăng ký (OTP/social/password đều khác
     * nhau, bootstrap là điểm hội tụ chung).
     *
     * Ghi theo `user_id`, KHÔNG theo `resident_id` — nhóm này không phải cứ
     * dân riêng của dự án nào, và tier `member` gọi hàm này có thể chưa có
     * Resident nào cả.
     *
     * Hiện hệ thống chỉ có ĐÚNG MỘT nhóm `platform_community` — không lọc
     * theo `tenant_id IS NULL` như đích cuối `COMMUNITY_DB_MAPPING.md` §8 vì
     * dữ liệu thật hiện tại nhóm này vẫn mang `tenant_id=1` (seed từ Giai đoạn
     * trước đó chưa null hoá cột này — nợ riêng, không thuộc phạm vi Giai đoạn
     * 3, ghi nhận ở DEV_JOURNAL).
     */
    public function enrollPlatformCommunity(User $user): ?CommunityGroupMember
    {
        $group = CommunityGroup::withoutGlobalScopes()
            ->where('group_type', CommunityGroupType::PlatformCommunity->value)
            ->first();

        if ($group === null) {
            return null;
        }

        // Guard rẻ trước khi vào transaction/lock: bootstrap được app gọi ở
        // MỌI lần mở app, không phải chỉ lần đầu — phần lớn lượt gọi phải là
        // no-op tức thì, không phải lock hàng mỗi lần.
        $existing = CommunityGroupMember::query()
            ->where('community_group_id', $group->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();
        if ($existing !== null) {
            return $existing;
        }

        return $this->grant($group, CommunityMembershipGrant::SOURCE_SYSTEM_ENROLLMENT, null, userId: $user->id);
    }
}
