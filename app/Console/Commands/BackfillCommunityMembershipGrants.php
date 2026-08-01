<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CommunityGroupMember;
use App\Models\CommunityMembershipGrant;
use Illuminate\Console\Command;

/**
 * Backfill Giai đoạn 3 (grants & membership) — `COMMUNITY_DB_MAPPING.md` §3,
 * `COMMUNITY_IMPLEMENTATION_PLAN.md` Giai đoạn 3 mục 2.
 *
 * Mỗi `community_group_members` hiện có → một grant:
 *  - nhóm `is_default = true` → `system_enrollment` (thành viên mặc định của
 *    dự án/hệ thống — không phải ai đó chủ động bấm "tham gia");
 *  - còn lại → `manual_join`.
 *
 * `source_id = null` cho cả hai — dữ liệu cũ (trước khi grants tồn tại) không
 * biết quan hệ căn hộ CỤ THỂ nào (nếu có) đã dẫn tới lượt tham gia đó.
 * Idempotent theo unique (`membership_id`,`source_type`,`source_id`).
 */
class BackfillCommunityMembershipGrants extends Command
{
    protected $signature = 'community:backfill-membership-grants
                            {--rollback : Xoá toàn bộ community_membership_grants do lệnh này tạo (community_group_members giữ nguyên)}
                            {--dry-run : Chỉ đếm, không ghi}';

    protected $description = 'Backfill community_membership_grants từ community_group_members hiện có (Giai đoạn 3)';

    public function handle(): int
    {
        if ($this->option('rollback')) {
            $n = CommunityMembershipGrant::query()->delete();
            $this->info("Đã xoá {$n} dòng community_membership_grants. community_group_members giữ nguyên.");

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        $members = CommunityGroupMember::query()
            ->join('community_groups', 'community_groups.id', '=', 'community_group_members.community_group_id')
            ->select('community_group_members.*', 'community_groups.is_default as group_is_default')
            ->get();

        $created = 0;
        foreach ($members as $member) {
            $sourceType = $member->group_is_default
                ? CommunityMembershipGrant::SOURCE_SYSTEM_ENROLLMENT
                : CommunityMembershipGrant::SOURCE_MANUAL_JOIN;

            if ($dryRun) {
                $exists = CommunityMembershipGrant::query()
                    ->where('membership_id', $member->id)
                    ->where('source_type', $sourceType)
                    ->whereNull('source_id')
                    ->exists();
                if (! $exists) {
                    $created++;
                }

                continue;
            }

            $grant = CommunityMembershipGrant::firstOrNew([
                'membership_id' => $member->id,
                'source_type' => $sourceType,
                'source_id' => null,
            ]);
            if (! $grant->exists) {
                $grant->status = CommunityMembershipGrant::STATUS_ACTIVE;
                $grant->granted_at = $member->joined_at ?? $member->created_at ?? now();
                $grant->save();
                $created++;
            }
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."Backfill {$created} grant, qua {$members->count()} membership hiện có.");

        return self::SUCCESS;
    }
}
