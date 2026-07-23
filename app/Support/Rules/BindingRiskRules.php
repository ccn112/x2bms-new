<?php

declare(strict_types=1);

namespace App\Support\Rules;

use App\Models\ResidentBindingRequest;

/**
 * Rule rủi ro khi duyệt gắn tài khoản ↔ căn hộ (rule-based, KHÔNG LLM).
 * Signal cross-record (số tài khoản nghi trùng, căn đã có chủ) do caller tính rồi truyền vào
 * (màn 03 ResidentBindingQueue). Signal per-request đọc từ chính yêu cầu + account.
 */
final class BindingRiskRules
{
    public static function forRequest(ResidentBindingRequest $r, int $duplicateCount = 0, bool $unitTaken = false): RiskReport
    {
        $report = new RiskReport;
        $acc = $r->account;

        if ($acc && $acc->identity_status !== 'verified') {
            $report->add(RiskFinding::warning(
                'identity_not_verified',
                'Tài khoản chưa xác thực định danh ('.($acc->identity_status ?? 'unverified').').',
                ['Xác thực SĐT/email/giấy tờ trước khi gắn căn.'],
            ));
        }

        if ($duplicateCount > 0) {
            $report->add(RiskFinding::highRisk(
                'duplicate_identity',
                $duplicateCount.' tài khoản nghi trùng (SĐT/email/nhóm trùng).',
                ['Đối chiếu để tránh gắn căn cho hồ sơ nhân bản.'],
            ));
        }

        if ($unitTaken && $r->requested_role === 'owner') {
            $report->add(RiskFinding::highRisk(
                'unit_owner_taken',
                'Căn hộ đã có chủ sở hữu đang hoạt động.',
                ['Kiểm tra tranh chấp/chuyển nhượng trước khi duyệt vai trò chủ sở hữu.'],
            ));
        }

        if (empty(data_get($r->evidence_files_json, 'evidence', []))) {
            $report->add(RiskFinding::info(
                'no_evidence',
                'Chưa đính kèm minh chứng.',
                ['Yêu cầu bổ sung giấy tờ chủ quyền / hợp đồng nếu cần.'],
            ));
        }

        return $report;
    }
}
