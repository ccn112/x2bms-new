<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Models\AiRetrievalLog;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * WEB-UX-22-12 — Nhật ký kế thừa, chia sẻ, clone, index AI và truy xuất AI.
 *
 * Mọi hành động nhạy cảm đều hiện trong audit (AC-33). Chi tiết retrieval AI cho thấy
 * tài liệu dùng/bị chặn + snapshot quyền + token (AC-25/26/27). Export tôn trọng quyền (AC-34).
 */
class KnowledgeAuditLog extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;

    protected static function platformFeature(): ?string
    {
        return 'ai_audit';
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng (SuperAdmin)';

    protected static ?string $navigationLabel = 'Nhật ký kế thừa & AI';

    protected static ?int $navigationSort = 62;

    protected static ?string $title = 'Nhật ký kế thừa & truy xuất AI';

    protected static ?string $slug = 'platform/knowledge-audit';

    protected string $view = 'filament.pages.knowledge-audit-log';

    /** Nhóm action governance quan tâm (event types). */
    public const EVENT_PREFIXES = ['binding.', 'template.', 'kb.', 'partner.', 'content.', 'account.', 'public_project.', 'ai.'];

    public const EVENT_LABEL = [
        'template.share' => 'Chia sẻ mẫu', 'template.clone' => 'Clone mẫu', 'template.policy_apply' => 'Áp chính sách mẫu',
        'template.policy_rollback' => 'Gỡ chính sách mẫu', 'kb.share' => 'Chia sẻ KB', 'kb.index_ai' => 'Index KB',
        'kb.archive' => 'Lưu trữ KB', 'binding.approve' => 'Duyệt gắn căn', 'binding.reject' => 'Từ chối gắn căn',
        'ai.guardrail_toggle' => 'Bật/tắt guardrail', 'ai.prompt_create' => 'Tạo prompt',
    ];

    /** @return \Illuminate\Database\Eloquent\Builder<AuditLog> */
    private function scoped()
    {
        return AuditLog::query()->where(function ($q) {
            foreach (self::EVENT_PREFIXES as $p) {
                $q->orWhere('action', 'like', $p.'%');
            }
        });
    }

    protected function getViewData(): array
    {
        $tokens = (int) AiRetrievalLog::sum(\DB::raw('token_input + token_output'));

        return [
            'kpis' => [
                ['label' => 'Sự kiện governance', 'value' => $this->scoped()->count(), 'accent' => 'blue'],
                ['label' => 'Lượt truy xuất AI', 'value' => AiRetrievalLog::count(), 'accent' => 'blue'],
                ['label' => 'Tài liệu bị chặn', 'value' => AiRetrievalLog::where('blocked_document_ids_json', '!=', '[]')->whereNotNull('blocked_document_ids_json')->count(), 'accent' => 'red'],
                ['label' => 'Tổng token AI', 'value' => number_format($tokens), 'accent' => 'blue'],
            ],
            'retrievals' => AiRetrievalLog::with('user')->latest('created_at')->limit(10)->get(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->scoped())
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('action')->label('Sự kiện')->badge()->color('info')->searchable()
                    ->formatStateUsing(fn (string $state) => self::EVENT_LABEL[$state] ?? $state),
                TextColumn::make('actor_name')->label('Người thực hiện')->placeholder('—')->searchable(),
                TextColumn::make('description')->label('Đối tượng')->wrap()->searchable(),
                TextColumn::make('subject_type')->label('Loại')->formatStateUsing(fn (?string $s) => $s ? class_basename($s) : '—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('action')->label('Loại sự kiện')->options(self::EVENT_LABEL),
            ])
            ->headerActions([
                Action::make('export')->label('Xuất CSV')->icon('heroicon-m-arrow-down-tray')->color('gray')
                    ->action(fn (): StreamedResponse => $this->exportCsv()),
            ])
            ->emptyStateHeading('Chưa có sự kiện')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    /** Chi tiết 1 lượt retrieval AI (mở từ blade qua mountAction). */
    public function retrievalDetailAction(): Action
    {
        return Action::make('retrievalDetail')
            ->modalHeading('Chi tiết truy xuất AI')
            ->modalContent(function (array $arguments) {
                $log = AiRetrievalLog::with('user')->find($arguments['id'] ?? 0);

                return view('filament.pages.retrieval-detail', ['log' => $log]);
            })
            ->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    private function exportCsv(): StreamedResponse
    {
        $rows = $this->scoped()->latest('created_at')->limit(1000)->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Thời gian', 'Sự kiện', 'Người thực hiện', 'Đối tượng']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->created_at, $r->action, $r->actor_name, $r->description]);
            }
            fclose($out);
        }, 'knowledge-audit-'.now()->format('Ymd_His').'.csv');
    }
}
