<?php

namespace App\Enums;

/**
 * Mức xác minh của nhóm cộng đồng (docs 01 §3).
 *
 * Nhãn trả **từ server**: badge luôn phải có nhãn ngữ nghĩa cho người dùng trình
 * đọc màn hình, và nhãn đó là chuyện nghiệp vụ chứ không phải chuyện hiển thị.
 * App chỉ vẽ, không tự đặt chữ.
 */
enum CommunityVerificationLevel: string
{
    case None = 'none';
    case BqlOfficial = 'bql_official';
    case PlatformVerified = 'platform_verified';

    public function label(): ?string
    {
        return match ($this) {
            self::BqlOfficial => 'Chính thức từ Ban quản lý',
            self::PlatformVerified => 'Đã xác minh bởi X2Living',
            self::None => null,
        };
    }

    /** Khoá biểu tượng — app map sang icon, KHÔNG map sang màu tại chỗ. */
    public function badgeKey(): ?string
    {
        return match ($this) {
            self::BqlOfficial => 'verified_official',
            self::PlatformVerified => 'verified_platform',
            self::None => null,
        };
    }
}
