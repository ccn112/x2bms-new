<?php

declare(strict_types=1);

namespace App\Support\Rules;

/**
 * 4 mức cảnh báo rule-based (chốt BQL_MASTER_BUILD_PLAN §0). KHÔNG gọi LLM.
 * `policy_block` = chặn nút quyết định (chỉ HQ/SuperAdmin override + ghi audit).
 */
final class RiskLevel
{
    public const INFO = 'info';
    public const WARNING = 'warning';
    public const HIGH_RISK = 'high_risk';
    public const POLICY_BLOCK = 'policy_block';

    private const SEVERITY = [
        self::INFO => 0,
        self::WARNING => 1,
        self::HIGH_RISK => 2,
        self::POLICY_BLOCK => 3,
    ];

    public static function severity(string $level): int
    {
        return self::SEVERITY[$level] ?? 0;
    }

    /** Tone cho x-x2.status-badge (green|amber|red|slate). */
    public static function tone(string $level): string
    {
        return match ($level) {
            self::WARNING => 'amber',
            self::HIGH_RISK, self::POLICY_BLOCK => 'red',
            default => 'slate',
        };
    }

    public static function label(string $level): string
    {
        return match ($level) {
            self::INFO => 'Thông tin',
            self::WARNING => 'Cảnh báo',
            self::HIGH_RISK => 'Rủi ro cao',
            self::POLICY_BLOCK => 'Chặn duyệt',
            default => $level,
        };
    }
}
