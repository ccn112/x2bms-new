<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 6 không gian cộng đồng (`docs 01_LOCKED_DECISIONS.md` §2 của
 * `X2_BMS_COMMUNITY_DOMAIN_HANDOFF_20260729`, COM-001).
 *
 * Thay dần cột `kind` (4 giá trị: platform|project_interest|project_resident|
 * private) — `kind` GIỮ NGUYÊN ít nhất một release (app đang đọc), cột này
 * cộng thêm cạnh (`COMMUNITY_DB_MAPPING.md` §2).
 */
enum CommunityGroupType: string
{
    case PlatformCommunity = 'platform_community';
    case ProjectInterestChannel = 'project_interest_channel';
    case OfficialResidentGroup = 'official_resident_group';
    case PlatformVerifiedResidentGroup = 'platform_verified_resident_group';
    case ResidentCustomGroup = 'resident_custom_group';
    case ResidentInterestGroup = 'resident_interest_group';

    public function label(): string
    {
        return match ($this) {
            self::PlatformCommunity => 'Cộng đồng X2Living',
            self::ProjectInterestChannel => 'Kênh quan tâm dự án',
            self::OfficialResidentGroup => 'Nhóm cư dân chính thức (BQL)',
            self::PlatformVerifiedResidentGroup => 'Nhóm cư dân đã xác minh (chưa có BQL)',
            self::ResidentCustomGroup => 'Nhóm cư dân tự lập',
            self::ResidentInterestGroup => 'Câu lạc bộ / sở thích',
        };
    }

    /**
     * Suy `group_type` từ `kind` cũ (backfill 2026-07-31). `private` → LUÔN
     * `ResidentCustomGroup` — chủ dự án chốt 2026-07-31: cả 11 nhóm `private`
     * hiện có đều do cư dân tự lập, không phải câu lạc bộ sở thích do BQL/hệ
     * thống dựng. `ResidentInterestGroup` do đó CHƯA có nhóm nào mang giá trị
     * này — chỉ dùng khi tạo nhóm mới thuộc loại đó.
     */
    public static function fromLegacyKind(string $kind): self
    {
        return match ($kind) {
            'platform' => self::PlatformCommunity,
            'project_interest' => self::ProjectInterestChannel,
            'project_resident' => self::OfficialResidentGroup,
            'private' => self::ResidentCustomGroup,
            default => self::ResidentCustomGroup,
        };
    }
}
