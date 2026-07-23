<?php

namespace App\Filament\Pages;

use App\Models\GlobalUserAccount;
use App\Models\Resident;
use App\Models\ResidentApprovalRequest;
use App\Models\ResidentBindingRequest;
use App\Models\ResidentUnitBinding;
use App\Support\Context\CurrentContext;
use App\Support\Rules\AccountActivationRules;
use App\Support\Rules\ApprovalRiskRules;
use App\Support\Rules\BindingRiskRules;
use App\Support\Rules\DataQualityRules;
use App\Support\Rules\RiskLevel;
use App\Support\Rules\RiskReport;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

/**
 * BQL-02-10 — Trung tâm rule/AI duyệt (Approval Rule Center).
 * Gom cảnh báo rule-based (Module 0) từ 4 luồng: duyệt cư dân, chất lượng dữ liệu,
 * kích hoạt tài khoản, gắn căn. Human-gate — KHÔNG gọi LLM. Chỉ đọc, mỗi mục link về màn xử lý.
 */
class ApprovalRuleCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationLabel = 'Trung tâm rule/AI duyệt';

    protected static ?int $navigationSort = 14;

    protected static ?string $title = 'Trung tâm rule/AI duyệt';

    protected static ?string $slug = 'approval-ai-copilot';

    protected string $view = 'filament.pages.approval-rule-center';

    private const SCAN_LIMIT = 100;

    private const TOP_PER_SOURCE = 8;

    /** @return array<int> */
    private function buildingIds(): array
    {
        return app(CurrentContext::class)->buildingIds() ?: [0];
    }

    protected function getViewData(): array
    {
        $tally = ['policy_block' => 0, 'high_risk' => 0, 'warning' => 0, 'info' => 0];
        $sources = [];

        $sources[] = $this->buildSource(
            'approvals', 'Duyệt cư dân', 'heroicon-o-user-plus', url('/admin/resident-approvals'),
            ResidentApprovalRequest::query()->where('status', 'pending')
                ->when(true, fn (Builder $q) => $q->whereIn('building_id', $this->buildingIds()))
                ->latest()->limit(self::SCAN_LIMIT)->get(),
            fn ($r) => ApprovalRiskRules::forRequest($r),
            fn ($r) => $r->full_name ?: ('#'.$r->id),
            fn ($r) => url('/admin/residents/approvals/'.$r->id),
            $tally,
        );

        $sources[] = $this->buildSource(
            'data_quality', 'Chất lượng dữ liệu', 'heroicon-o-shield-exclamation', url('/admin/residents/data-quality'),
            Resident::query()->whereIn('building_id', $this->buildingIds())->latest('updated_at')->limit(self::SCAN_LIMIT)->get(),
            fn ($r) => DataQualityRules::forResident($r),
            fn ($r) => $r->full_name ?: ('#'.$r->id),
            fn ($r) => url('/admin/residents/'.$r->id.'/detail'),
            $tally,
        );

        $accountIds = ResidentUnitBinding::query()->whereIn('building_id', $this->buildingIds())->pluck('user_account_id')->unique();
        $sources[] = $this->buildSource(
            'activation', 'Kích hoạt tài khoản', 'heroicon-o-key', url('/admin/resident-accounts/activations'),
            GlobalUserAccount::query()->whereIn('id', $accountIds)->limit(self::SCAN_LIMIT)->get(),
            fn ($a) => AccountActivationRules::forAccount($a, 0),
            fn ($a) => $a->full_name ?: ($a->phone ?: '#'.$a->id),
            fn ($a) => url('/admin/resident-accounts/'.$a->id.'/detail'),
            $tally,
        );

        $sources[] = $this->buildSource(
            'binding', 'Gắn căn hộ', 'heroicon-o-identification', url('/admin/residents/binding-queue'),
            ResidentBindingRequest::withoutGlobalScope('tenant')->where('status', 'pending')
                ->with('account')->latest('requested_at')->limit(self::SCAN_LIMIT)->get(),
            fn ($r) => BindingRiskRules::forRequest($r),
            fn ($r) => ($r->account?->full_name ?: $r->code ?: '#'.$r->id),
            fn ($r) => url('/admin/residents/binding-queue'),
            $tally,
        );

        return [
            'tally' => $tally,
            'sources' => $sources,
        ];
    }

    /**
     * Chạy rule cho 1 nguồn, cộng dồn tally + trả top mục cần chú ý (nặng trước).
     *
     * @param  \Illuminate\Support\Collection<int,mixed>  $records
     */
    private function buildSource(string $key, string $label, string $icon, string $url, $records, callable $rule, callable $name, callable $link, array &$tally): array
    {
        $flagged = [];
        foreach ($records as $rec) {
            /** @var RiskReport $report */
            $report = $rule($rec);
            if ($report->isEmpty()) {
                continue;
            }
            foreach ($report->all() as $f) {
                if (isset($tally[$f->level])) {
                    $tally[$f->level]++;
                }
            }
            $flagged[] = [
                'name' => $name($rec),
                'url' => $link($rec),
                'severity' => RiskLevel::severity($report->highestLevel()),
                'findings' => array_map(fn (array $f) => [
                    'label' => $f['message'], 'tone' => RiskLevel::tone($f['level']),
                ], $report->toArray()),
            ];
        }

        usort($flagged, fn ($a, $b) => $b['severity'] <=> $a['severity']);

        return [
            'key' => $key, 'label' => $label, 'icon' => $icon, 'url' => $url,
            'flagged_count' => count($flagged),
            'top' => array_slice($flagged, 0, self::TOP_PER_SOURCE),
        ];
    }
}
