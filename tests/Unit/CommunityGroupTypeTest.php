<?php

namespace Tests\Unit;

use App\Enums\CommunityGroupType;
use PHPUnit\Framework\TestCase;

/**
 * Ánh xạ `kind` cũ → `group_type` mới (Giai đoạn 2 Community Domain, chốt
 * 2026-07-31: 11 nhóm `private` hiện có đều là cư dân tự lập).
 */
class CommunityGroupTypeTest extends TestCase
{
    public function test_anh_xa_dung_bon_kind_hien_co(): void
    {
        $this->assertSame(CommunityGroupType::PlatformCommunity, CommunityGroupType::fromLegacyKind('platform'));
        $this->assertSame(CommunityGroupType::ProjectInterestChannel, CommunityGroupType::fromLegacyKind('project_interest'));
        $this->assertSame(CommunityGroupType::OfficialResidentGroup, CommunityGroupType::fromLegacyKind('project_resident'));
        $this->assertSame(CommunityGroupType::ResidentCustomGroup, CommunityGroupType::fromLegacyKind('private'));
    }

    public function test_kind_la_khong_ro_ve_resident_custom_group_khong_ve_interest(): void
    {
        // ResidentInterestGroup chỉ dùng khi TẠO nhóm mới thuộc loại đó — backfill
        // dữ liệu cũ không được đoán bừa vào loại quyền khác.
        $this->assertSame(CommunityGroupType::ResidentCustomGroup, CommunityGroupType::fromLegacyKind('unknown_value'));
    }

    public function test_moi_group_type_co_nhan(): void
    {
        foreach (CommunityGroupType::cases() as $case) {
            $this->assertNotSame('', $case->label());
        }
    }
}
