<?php

declare(strict_types=1);

namespace App\Support\Rules;

use App\Models\Resident;
use App\Models\ResidentApprovalRequest;

/**
 * Rule đánh giá rủi ro cho yêu cầu duyệt cư dân (rule-based, KHÔNG LLM).
 * Field bám schema thật: resident_approval_requests.{match_score,document_count,phone,email,
 * apartment_id,requested_role,building_id}.
 *
 * Ngưỡng match_score (0..100): <50 rủi ro cao, 50..79 cảnh báo, >=80 đạt.
 */
final class ApprovalRiskRules
{
    public const MATCH_HIGH_RISK = 50;
    public const MATCH_WARNING = 80;

    public static function forRequest(ResidentApprovalRequest $req): RiskReport
    {
        $report = new RiskReport;

        $score = (int) ($req->match_score ?? 0);
        if ($score < self::MATCH_HIGH_RISK) {
            $report->add(RiskFinding::highRisk(
                'low_match_score',
                "Độ khớp dữ liệu thấp ({$score}/100).",
                ['Đối chiếu thủ công thông tin cư dân với hồ sơ căn hộ trước khi duyệt.'],
            ));
        } elseif ($score < self::MATCH_WARNING) {
            $report->add(RiskFinding::warning(
                'medium_match_score',
                "Độ khớp dữ liệu trung bình ({$score}/100).",
                ['Kiểm tra lại các trường lệch (tên, SĐT, giấy tờ).'],
            ));
        }

        if ((int) ($req->document_count ?? 0) === 0) {
            $report->add(RiskFinding::warning(
                'no_documents',
                'Chưa đính kèm giấy tờ minh chứng nào.',
                ['Yêu cầu bổ sung CCCD / hợp đồng / giấy tờ chủ quyền.'],
            ));
        }

        if (blank($req->apartment_id)) {
            $report->add(RiskFinding::warning(
                'no_apartment',
                'Chưa gắn căn hộ cho yêu cầu.',
                ['Chọn căn hộ tương ứng trước khi duyệt.'],
            ));
        }

        if (blank($req->phone)) {
            $report->add(RiskFinding::info(
                'no_phone',
                'Yêu cầu chưa có số điện thoại.',
                ['Bổ sung SĐT để liên hệ xác minh.'],
            ));
        }

        // POLICY BLOCK: trùng danh tính — đã có cư dân đang hoạt động cùng SĐT/email
        // trong cùng toà nhà → chặn duyệt, chỉ HQ/SA override + ghi audit.
        if (self::hasActiveDuplicate($req)) {
            $report->add(RiskFinding::block(
                'duplicate_identity',
                'Đã tồn tại cư dân đang hoạt động trùng SĐT/email trong toà nhà này.',
                [
                    'Kiểm tra có phải trùng người thật không (tránh tạo hồ sơ trùng).',
                    'Nếu là người khác, xác minh lại thông tin liên hệ.',
                    'Chỉ HQ/SuperAdmin mới được override để duyệt.',
                ],
            ));
        }

        return $report;
    }

    private static function hasActiveDuplicate(ResidentApprovalRequest $req): bool
    {
        $phone = $req->phone;
        $email = $req->email;
        if (blank($phone) && blank($email)) {
            return false;
        }

        return Resident::query()
            ->where('building_id', $req->building_id)
            ->where('status', 'active')
            ->where(function ($q) use ($phone, $email): void {
                if (filled($phone)) {
                    $q->orWhere('phone', $phone)->orWhere('contact_phone', $phone);
                }
                if (filled($email)) {
                    $q->orWhere('email', $email)->orWhere('contact_email', $email);
                }
            })
            ->exists();
    }
}
