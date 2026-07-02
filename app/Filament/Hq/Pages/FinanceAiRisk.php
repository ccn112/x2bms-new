<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\AiInsight;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-05-10 — AI phân tích rủi ro công nợ & dự báo tài chính. */
class FinanceAiRisk extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|\UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'AI rủi ro tài chính';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'AI phân tích rủi ro công nợ & dự báo tài chính';

    protected static ?string $slug = 'finance/ai-risk';

    protected string $view = 'filament.hq.pages.finance-ai-risk';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $kpi = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'ai_risk_kpi')->get()
            ->mapWithKeys(fn ($m) => [$m->dimension['metric'] => (float) $m->value]);
        $forecast = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'ai_forecast')->orderBy('period')->get()
            ->map(fn ($m) => ['period' => $m->dimension['period'] ?? $m->period, 'actual' => (float) $m->dimension['actual'], 'target' => (float) $m->dimension['target']]);
        $risks = AiInsight::where('tenant_id', $tid)->where('category', 'debt_risk')
            ->orderByDesc('score')->get()->map(fn ($i) => [
                'name' => $i->title, 'group' => $i->metadata['group'] ?? '—', 'debt' => $i->metadata['debt_ty'] ?? 0,
                'score' => (float) $i->score, 'prob' => $i->metadata['recovery_prob'] ?? 0, 'delay' => $i->metadata['delay_days'] ?? 0,
                'action' => $i->recommendation, 'severity' => $i->severity, 'handler' => $i->metadata['handler'] ?? '—',
            ]);

        return ['kpi' => $kpi, 'forecast' => $forecast, 'risks' => $risks];
    }
}
