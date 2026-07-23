<?php

namespace Tests\Unit;

use App\Models\ResidentApprovalRequest;
use App\Support\Rules\ApprovalRiskRules;
use App\Support\Rules\RiskFinding;
use App\Support\Rules\RiskLevel;
use App\Support\Rules\RiskReport;
use PHPUnit\Framework\TestCase;

class RiskEngineTest extends TestCase
{
    public function test_severity_ordering(): void
    {
        $this->assertLessThan(
            RiskLevel::severity(RiskLevel::POLICY_BLOCK),
            RiskLevel::severity(RiskLevel::INFO),
        );
        $this->assertSame('red', RiskLevel::tone(RiskLevel::POLICY_BLOCK));
        $this->assertSame('amber', RiskLevel::tone(RiskLevel::WARNING));
        $this->assertSame('Chặn duyệt', RiskLevel::label(RiskLevel::POLICY_BLOCK));
    }

    public function test_finding_factories_set_level(): void
    {
        $this->assertSame(RiskLevel::WARNING, RiskFinding::warning('c', 'm')->level);
        $this->assertSame(RiskLevel::POLICY_BLOCK, RiskFinding::block('c', 'm', ['do'])->level);
        $this->assertSame(['do'], RiskFinding::block('c', 'm', ['do'])->checklist);
    }

    public function test_report_highest_level_and_blocked(): void
    {
        $report = new RiskReport([
            RiskFinding::info('a', 'x'),
            RiskFinding::warning('b', 'y'),
        ]);
        $this->assertFalse($report->isBlocked());
        $this->assertSame(RiskLevel::WARNING, $report->highestLevel());
        $this->assertSame('amber', $report->tone());

        $report->add(RiskFinding::block('c', 'z'));
        $this->assertTrue($report->isBlocked());
        $this->assertSame(RiskLevel::POLICY_BLOCK, $report->highestLevel());
        $this->assertSame(2, $report->countFrom(RiskLevel::WARNING));
    }

    public function test_empty_report_defaults_green(): void
    {
        $report = new RiskReport;
        $this->assertTrue($report->isEmpty());
        $this->assertNull($report->highestLevel());
        $this->assertSame('green', $report->tone());
    }

    public function test_report_ai_context_shape(): void
    {
        $report = new RiskReport([RiskFinding::warning('c', 'Thiếu SĐT', ['Bổ sung SĐT'])]);
        $ctx = $report->toAiContext();
        $this->assertCount(1, $ctx['lines']);
        $this->assertStringContainsString('Cảnh báo', $ctx['lines'][0]);
        $this->assertSame('Bổ sung SĐT', $ctx['suggestions'][0]['title']);
    }

    public function test_approval_rules_pure_branches(): void
    {
        // phone/email null → bỏ qua truy vấn trùng danh tính (không cần DB).
        $req = new ResidentApprovalRequest([
            'match_score' => 30,
            'document_count' => 0,
            'apartment_id' => null,
            'phone' => null,
            'email' => null,
            'building_id' => 1,
        ]);

        $report = ApprovalRiskRules::forRequest($req);
        $codes = array_column($report->toArray(), 'code');

        $this->assertContains('low_match_score', $codes);
        $this->assertContains('no_documents', $codes);
        $this->assertContains('no_apartment', $codes);
        $this->assertFalse($report->isBlocked());
        $this->assertSame(RiskLevel::HIGH_RISK, $report->highestLevel());
    }

    public function test_approval_rules_high_score_no_warning(): void
    {
        $req = new ResidentApprovalRequest([
            'match_score' => 95,
            'document_count' => 2,
            'apartment_id' => 5,
            'phone' => null,
            'email' => null,
            'building_id' => 1,
        ]);

        $report = ApprovalRiskRules::forRequest($req);
        $codes = array_column($report->toArray(), 'code');

        $this->assertNotContains('low_match_score', $codes);
        $this->assertNotContains('medium_match_score', $codes);
        $this->assertNotContains('no_documents', $codes);
        $this->assertNotContains('no_apartment', $codes);
    }
}
