<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ProvidesAiContext;
use App\Models\AiWorkflow;
use App\Models\AiWorkflowRun;
use BackedEnum;
use Filament\Pages\Page;

/**
 * WEB-UX-09-03 — Thiết kế Workflow Automation. Trigger/workflow list on the left,
 * a node flow + config panel for the selected workflow in the middle, run stats and
 * an execution log at the bottom. Selection is client-side (Alpine); all workflows
 * and their runs come from ai_workflows / ai_workflow_runs.
 */
class AiWorkflowAutomation extends Page
{
    use ProvidesAiContext;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static string|\UnitEnum|null $navigationGroup = 'X2 AI Engine';

    protected static ?string $navigationLabel = 'Workflow Automation';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Thiết kế Workflow Automation';

    protected static ?string $slug = 'ai/workflows';

    protected string $view = 'filament.pages.ai-workflow-automation';

    protected function getViewData(): array
    {
        $workflows = AiWorkflow::with(['runs' => fn ($q) => $q->latest('started_at')->limit(6)])
            ->orderByDesc('status')->orderByDesc('runs_count')->get();

        $totalRuns = (int) AiWorkflow::sum('runs_count');
        $totalOk = (int) AiWorkflow::sum('success_count');

        $this->shareAiContext([
            'title' => 'Tự động hoá quy trình',
            'lines' => [$workflows->where('status', 'active')->count().' workflow đang chạy, tổng '.number_format($totalRuns).' lần thực thi.'],
        ]);

        return [
            'kpis' => [
                ['label' => 'Tổng workflow', 'value' => $workflows->count(), 'accent' => 'blue'],
                ['label' => 'Đang chạy', 'value' => $workflows->where('status', 'active')->count(), 'accent' => 'green'],
                ['label' => 'Tổng lần chạy', 'value' => number_format($totalRuns), 'accent' => 'teal'],
                ['label' => 'Tỷ lệ thành công', 'value' => ($totalRuns ? round($totalOk / $totalRuns * 100, 1) : 0).'%', 'accent' => 'amber'],
            ],
            'workflows' => $workflows,
            'recentRuns' => AiWorkflowRun::with('workflow')->latest('started_at')->limit(8)->get(),
        ];
    }
}
