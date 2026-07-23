<?php

declare(strict_types=1);

namespace App\Support\Rules;

use App\Models\GlobalUserAccount;

/**
 * Rule rủi ro khi kích hoạt tài khoản cư dân (rule-based, KHÔNG LLM).
 * Bám schema thật: global_user_accounts.{identity_status,account_status,risk_score,duplicate_group_id}
 * + số thiết bị đã đăng nhập app (MobileDevice) truyền vào.
 */
final class AccountActivationRules
{
    public const RISK_SCORE_WARN = 50;

    public static function forAccount(GlobalUserAccount $account, int $activeDeviceCount = 0): RiskReport
    {
        $report = new RiskReport;

        if ($account->identity_status !== 'verified') {
            $report->add(RiskFinding::warning(
                'identity_not_verified',
                'Định danh chưa xác thực đầy đủ ('.($account->identity_status ?? 'unverified').').',
                ['Xác thực SĐT/email/giấy tờ trước khi kích hoạt tài khoản.'],
            ));
        }

        if (filled($account->duplicate_group_id)) {
            $report->add(RiskFinding::highRisk(
                'duplicate_identity',
                'Nghi trùng danh tính (cùng nhóm trùng SĐT/email).',
                ['Đối chiếu các tài khoản cùng nhóm trùng để tránh kích hoạt hồ sơ nhân bản.'],
            ));
        }

        if ((int) ($account->risk_score ?? 0) >= self::RISK_SCORE_WARN) {
            $report->add(RiskFinding::warning(
                'high_risk_score',
                'Điểm rủi ro cao ('.$account->risk_score.').',
                ['Rà soát lịch sử/hành vi tài khoản trước khi mời kích hoạt.'],
            ));
        }

        if ($account->account_status === 'suspended') {
            $report->add(RiskFinding::info(
                'account_suspended',
                'Tài khoản đang bị khóa.',
                ['Mở khóa nếu muốn cho phép đăng nhập lại.'],
            ));
        }

        if ($activeDeviceCount === 0) {
            $report->add(RiskFinding::info(
                'no_device',
                'Chưa có thiết bị nào đăng nhập app.',
                ['Gửi lời mời kích hoạt để cư dân cài app và đăng nhập lần đầu.'],
            ));
        }

        return $report;
    }
}
