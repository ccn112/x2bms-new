<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ProvidesAiContext;
use App\Models\AiPromptTemplate;
use App\Models\AiUsageLog;
use App\Models\AiWorkflow;
use App\Models\KnowledgeArticle;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;

/**
 * WEB-UX-09-01 — Trung tâm AI X2AI. Command center for the AI engine: usage KPIs,
 * a 14-day usage trend, per-screen Copilot usage, automation summary, items waiting
 * for human approval, knowledge-base reach and quick prompts. All values derived
 * from ai_* tables (tenant-scoped) — nothing hardcoded. The chat itself stays in the
 * shared floating FAB.
 */
class AiCenter extends Page
{
    use ProvidesAiContext;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|\UnitEnum|null $navigationGroup = 'X2 AI Engine';

    protected static ?string $navigationLabel = 'Trung tâm AI';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Trung tâm AI X2AI';

    protected static ?string $slug = 'ai/center';

    protected string $view = 'filament.pages.ai-center';

    protected function getViewData(): array
    {
        $since = Carbon::parse('2026-06-30')->subDays(30);
        $base = AiUsageLog::where('created_at', '>=', $since);

        $total = (clone $base)->count();
        $success = (clone $base)->where('status', 'success')->count();
        $successRate = $total ? round($success / $total * 100, 1) : 0;
        $savedHours = round($total * 6 / 60, 1); // ~6 phút thao tác thủ công / lượt
        $cost = (clone $base)->sum('cost');

        // 14-day trend (oldest → newest).
        $trend = [];
        for ($d = 13; $d >= 0; $d--) {
            $day = Carbon::parse('2026-06-30')->subDays($d);
            $count = AiUsageLog::whereDate('created_at', $day)->count();
            $trend[] = ['label' => $day->format('d/m'), 'count' => $count];
        }
        $trendMax = max(1, collect($trend)->max('count'));

        $bySurface = (clone $base)
            ->selectRaw('surface, count(*) as c')
            ->groupBy('surface')->orderByDesc('c')->get()
            ->map(fn ($r) => ['surface' => self::SURFACE_LABELS[$r->surface] ?? $r->surface, 'count' => $r->c]);

        $pendingApproval = AiUsageLog::where('status', 'pending_approval')
            ->latest()->get();

        $this->shareAiContext([
            'title' => 'Tổng quan AI',
            'lines' => [
                "Đã dùng AI {$total} lượt trong 30 ngày, tỉ lệ thành công {$successRate}%.",
                'Có '.$pendingApproval->count().' hành động rủi ro cao đang chờ duyệt.',
            ],
        ]);

        return [
            'kpis' => [
                ['label' => 'Lượt dùng AI (30 ngày)', 'value' => number_format($total), 'accent' => 'blue'],
                ['label' => 'Tỷ lệ thành công', 'value' => $successRate.'%', 'accent' => 'green'],
                ['label' => 'Workflow đang chạy', 'value' => AiWorkflow::where('status', 'active')->count(), 'accent' => 'teal'],
                ['label' => 'Giờ tiết kiệm (ước tính)', 'value' => number_format($savedHours, 1), 'sub' => 'so với thao tác thủ công', 'accent' => 'amber'],
                ['label' => 'Chi phí AI (30 ngày)', 'value' => number_format($cost).' đ', 'accent' => 'blue'],
            ],
            'trend' => $trend,
            'trendMax' => $trendMax,
            'bySurface' => $bySurface,
            'surfaceMax' => max(1, $bySurface->max('count') ?? 1),
            'workflows' => AiWorkflow::orderByDesc('runs_count')->limit(5)->get(),
            'pendingApproval' => $pendingApproval,
            'kbCount' => KnowledgeArticle::visibleTo(auth()->user())->where('status', 'published')->count(),
            'kbViews' => KnowledgeArticle::visibleTo(auth()->user())->sum('views'),
            'quickPrompts' => AiPromptTemplate::where('status', 'active')->orderByDesc('usage_count')->limit(6)->get(),
            'recent' => AiUsageLog::with('user')->latest()->limit(6)->get(),
        ];
    }

    public const SURFACE_LABELS = [
        'finance/statement-approvals' => 'Duyệt bảng kê',
        'residents/create' => 'Cư dân',
        'operational-dashboard' => 'Bảng điều hành',
        'feedback' => 'Phản ánh / CSKH',
        'work-orders' => 'Lệnh làm việc',
        'knowledge-base' => 'Cơ sở tri thức',
    ];
}
