<?php

declare(strict_types=1);

namespace App\Support\Rules;

use App\Models\Resident;

/**
 * Rule chất lượng dữ liệu hồ sơ cư dân (rule-based, KHÔNG LLM).
 * Dùng ở màn chi tiết cư dân + duyệt để nhắc BQL bổ sung trước khi kích hoạt.
 * Field bám schema thật: residents.{phone,email,id_no,dob,kyc_status,face_match_status,documents}.
 */
final class DataQualityRules
{
    public static function forResident(Resident $resident): RiskReport
    {
        $report = new RiskReport;

        if (blank($resident->phone) && blank($resident->contact_phone)) {
            $report->add(RiskFinding::warning(
                'missing_phone',
                'Chưa có số điện thoại liên hệ.',
                ['Bổ sung số điện thoại chính chủ để kích hoạt tài khoản app.'],
            ));
        }

        if (blank($resident->email) && blank($resident->contact_email)) {
            $report->add(RiskFinding::info(
                'missing_email',
                'Chưa có email.',
                ['Bổ sung email nếu cần gửi thông báo/hoá đơn điện tử.'],
            ));
        }

        if (blank($resident->id_no)) {
            $report->add(RiskFinding::warning(
                'missing_id_no',
                'Chưa có số CCCD/CMND.',
                ['Bổ sung số giấy tờ tuỳ thân để định danh.'],
            ));
        }

        if (blank($resident->dob)) {
            $report->add(RiskFinding::info(
                'missing_dob',
                'Chưa có ngày sinh.',
                ['Bổ sung ngày sinh cho hồ sơ đầy đủ.'],
            ));
        }

        if (in_array($resident->kyc_status, ['unverified', 'pending', 'rejected'], true)) {
            $report->add(RiskFinding::warning(
                'kyc_not_verified',
                'Định danh (KYC) chưa xác thực: '.($resident->kyc_status ?? 'unverified').'.',
                ['Kiểm tra ảnh giấy tờ + chân dung rồi xác thực KYC.'],
            ));
        }

        if ($resident->face_match_status === 'mismatch') {
            $report->add(RiskFinding::highRisk(
                'face_mismatch',
                'Ảnh chân dung KHÔNG khớp giấy tờ.',
                ['Đối chiếu lại ảnh chân dung với CCCD.', 'Yêu cầu cư dân nộp lại ảnh nếu cần.'],
            ));
        }

        return $report;
    }
}
