<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\ProvidesAiContext;
use App\Filament\Concerns\WritesAudit;
use App\Models\AiWorkflow;
use App\Models\AiWorkflowRun;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * WEB-UX-09-03 — Thiết kế Workflow Automation. Trigger/workflow list + node flow +
 * config + run log for the selected workflow. Write-actions: create (header),
 * edit (modal, per-workflow via mountAction), pause/activate and "chạy thử"
 * (Livewire methods). All tenant-scoped and audited.
 */
class AiWorkflowAutomation extends Page
{
    use ProvidesAiContext;
    use WritesAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static string|\UnitEnum|null $navigationGroup = 'X2 AI Engine';

    protected static ?string $navigationLabel = 'Workflow Automation';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Thiết kế Workflow Automation';

    protected static ?string $slug = 'ai/workflows';

    protected string $view = 'filament.pages.ai-workflow-automation';

    protected const DEFAULT_STEPS = [
        ['type' => 'trigger', 'label' => 'Kích hoạt'],
        ['type' => 'ai', 'label' => 'X2AI xử lý nội dung'],
        ['type' => 'condition', 'label' => 'Kiểm tra điều kiện'],
        ['type' => 'action', 'label' => 'Gửi thông báo / tạo việc'],
    ];

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

    /** @return array<int, \Filament\Forms\Components\Component> */
    private function workflowFormSchema(): array
    {
        return [
            TextInput::make('name')->label('Tên workflow')->required()->maxLength(160),
            Textarea::make('description')->label('Mô tả')->rows(2)->maxLength(255),
            Select::make('trigger_type')->label('Kiểu kích hoạt')
                ->options(['event' => 'Theo sự kiện', 'schedule' => 'Theo lịch', 'manual' => 'Thủ công'])
                ->default('event')->required()->live(),
            TextInput::make('schedule')->label('Lịch / điều kiện')
                ->placeholder('VD: Hằng ngày 08:00 · Khi có phản ánh mới')->maxLength(160),
            Select::make('status')->label('Trạng thái')
                ->options(['active' => 'Đang chạy', 'paused' => 'Tạm dừng', 'draft' => 'Nháp'])
                ->default('draft')->required(),
        ];
    }

    /** Header action: create a workflow. */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('createWorkflow')
                ->label('Tạo workflow')->icon('heroicon-m-plus')->color('primary')
                ->modalHeading('Tạo workflow tự động')
                ->schema($this->workflowFormSchema())
                ->action(function (array $data): void {
                    $wf = AiWorkflow::create($data + [
                        'project_id' => app(CurrentContext::class)->projectId(),
                        'steps' => self::DEFAULT_STEPS,
                        'created_by_id' => auth()->id(),
                    ]);
                    $this->audit('ai.workflow.create', 'Tạo workflow: '.$wf->name, AiWorkflow::class, $wf->id);
                    Notification::make()->title('Đã tạo workflow')->success()->send();
                }),
        ];
    }

    /** Per-workflow edit modal (triggered via mountAction('editWorkflow', { id })). */
    public function editWorkflowAction(): Action
    {
        return Action::make('editWorkflow')
            ->modalHeading('Sửa workflow')
            ->schema($this->workflowFormSchema())
            ->fillForm(fn (array $arguments): array => AiWorkflow::find($arguments['id'])
                ?->only(['name', 'description', 'trigger_type', 'schedule', 'status']) ?? [])
            ->action(function (array $arguments, array $data): void {
                $wf = AiWorkflow::find($arguments['id']);
                if (! $wf) {
                    return;
                }
                $wf->update($data);
                $this->audit('ai.workflow.update', 'Sửa workflow: '.$wf->name, AiWorkflow::class, $wf->id);
                Notification::make()->title('Đã cập nhật workflow')->success()->send();
            });
    }

    /** Tạm dừng / kích hoạt lại. */
    public function toggleWorkflow(int $id): void
    {
        $wf = AiWorkflow::find($id);
        if (! $wf) {
            return;
        }
        $wf->update(['status' => $wf->status === 'active' ? 'paused' : 'active']);
        $this->audit('ai.workflow.toggle', ($wf->status === 'active' ? 'Kích hoạt' : 'Tạm dừng').' workflow: '.$wf->name, AiWorkflow::class, $wf->id);
        Notification::make()->title($wf->status === 'active' ? 'Đã kích hoạt' : 'Đã tạm dừng')->success()->send();
    }

    /** Chạy thử: ghi 1 lần chạy thành công + tăng bộ đếm. */
    public function runWorkflow(int $id): void
    {
        $wf = AiWorkflow::find($id);
        if (! $wf) {
            return;
        }
        $now = now();
        AiWorkflowRun::create([
            'ai_workflow_id' => $wf->id,
            'status' => 'success',
            'trigger_source' => $wf->trigger_type,
            'duration_ms' => 900,
            'note' => 'Chạy thử thủ công',
            'started_at' => $now,
            'finished_at' => $now->copy()->addSeconds(1),
        ]);
        $wf->update([
            'runs_count' => $wf->runs_count + 1,
            'success_count' => $wf->success_count + 1,
            'last_run_at' => $now,
        ]);
        $this->audit('ai.workflow.run', 'Chạy thử workflow: '.$wf->name, AiWorkflow::class, $wf->id);
        Notification::make()->title('Đã chạy thử workflow')->success()->send();
    }
}
