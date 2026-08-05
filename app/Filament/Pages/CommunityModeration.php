<?php

namespace App\Filament\Pages;

use App\Actions\Community\ModerateCommunityPostAction;
use App\Filament\Concerns\WritesAudit;
use App\Models\CommunityPost;
use App\Models\CommunityPostReport;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

/**
 * BQL-07-08 — Kiểm duyệt cộng đồng. Thay Resource scaffold tự sinh (cột raw
 * `project_id`/`author_resident_id`, chỉ Edit/Delete mặc định) — spec đầy đủ:
 * `docs/COMMUNITY_WRITE_MODERATION_DESIGN.md` §4.
 *
 * Dùng chung state machine với app cư dân qua `ModerateCommunityPostAction` — hai
 * nơi (web BQL, app BQL-kiêm-cư dân) không được có hai bản logic khóa/ẩn/xóa
 * lệch nhau.
 *
 * CỐ Ý MỎNG so với thiết kế đầy đủ ở §4: chưa có màn chi tiết riêng (07-09, cây
 * bình luận drilldown) và chưa có bulk inline — xem `docs/PROGRESS_TRACKER.md`
 * hàng 07-08 cho phần còn lại.
 */
class CommunityModeration extends Page implements HasTable
{
    use InteractsWithTable;
    use WritesAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Vận hành';

    protected static ?string $navigationLabel = 'Kiểm duyệt cộng đồng';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Kiểm duyệt cộng đồng';

    protected static ?string $slug = 'community-moderation';

    protected string $view = 'filament.pages.community-moderation';

    private function projectId(): ?int
    {
        return app(CurrentContext::class)->projectId();
    }

    private function baseQuery(): Builder
    {
        return CommunityPost::withTrashed()->where('project_id', $this->projectId());
    }

    private function filteredQuery(): Builder
    {
        return $this->baseQuery()->with(['author', 'authorUser', 'group']);
    }

    private function refreshTable(): void
    {
        $this->resetPage($this->getTablePaginationPageName());
        $this->flushCachedTableRecords();
    }

    protected function getViewData(): array
    {
        $base = $this->baseQuery();
        $today = (clone $base)->whereNull('deleted_at')->whereDate('created_at', today())->count();
        $pendingReports = CommunityPostReport::query()
            ->whereHas('post', fn ($q) => $q->where('project_id', $this->projectId()))
            ->where('status', 'open')
            ->count();
        $locked = (clone $base)->whereNull('deleted_at')->whereNotNull('locked_at')->count();
        $hidden = (clone $base)->whereNull('deleted_at')->where('status', 'hidden')->count();
        $deleted = (clone $base)->whereNotNull('deleted_at')->count();

        return [
            'kpis' => [
                ['label' => 'Bài mới hôm nay', 'value' => $today, 'icon' => 'heroicon-o-document-plus', 'accent' => 'blue'],
                ['label' => 'Chờ xử lý report', 'value' => $pendingReports, 'icon' => 'heroicon-o-flag', 'accent' => 'amber'],
                ['label' => 'Đang khóa', 'value' => $locked, 'icon' => 'heroicon-o-lock-closed', 'accent' => 'orange'],
                ['label' => 'Đang ẩn', 'value' => $hidden, 'icon' => 'heroicon-o-eye-slash', 'accent' => 'red'],
                ['label' => 'Đã xóa mềm', 'value' => $deleted, 'icon' => 'heroicon-o-trash', 'accent' => 'gray'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->filteredQuery())
            ->defaultSort('report_count', 'desc')
            ->columns([
                TextColumn::make('author')
                    ->label('Tác giả')
                    ->state(fn (CommunityPost $p): string => $p->author?->full_name ?? $p->authorUser?->name ?? ($p->author_kind === 'staff' ? 'Ban quản lý' : '—'))
                    ->description(fn (CommunityPost $p): ?string => $p->author?->building?->name),
                TextColumn::make('body')
                    ->label('Nội dung')
                    ->limit(80)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('group.name')->label('Nhóm')->badge()->color('gray')->placeholder('Bảng tin chung'),
                TextColumn::make('interactions')
                    ->label('Tương tác')
                    ->state(fn (CommunityPost $p): string => "👍 {$p->like_count} · 💬 {$p->comment_count}"),
                TextColumn::make('report_count')
                    ->label('Report')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('state')
                    ->label('Trạng thái')
                    ->state(fn (CommunityPost $p): string => match (true) {
                        $p->deleted_at !== null => 'Đã xóa mềm',
                        $p->locked_at !== null => 'Đã khóa',
                        $p->status === 'hidden' => 'Đã ẩn',
                        default => 'Hiện',
                    })
                    ->badge()
                    ->color(fn (CommunityPost $p): string => match (true) {
                        $p->deleted_at !== null => 'gray',
                        $p->locked_at !== null => 'warning',
                        $p->status === 'hidden' => 'danger',
                        default => 'success',
                    }),
                TextColumn::make('created_at')->label('Ngày')->date('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label('Trạng thái')
                    ->options(['published' => 'Hiện', 'hidden' => 'Đã ẩn', 'locked' => 'Đã khóa', 'deleted' => 'Đã xóa mềm'])
                    ->query(fn (Builder $q, array $data): Builder => match ($data['value'] ?? null) {
                        'published' => $q->whereNull('deleted_at')->whereNull('locked_at')->where('status', 'published'),
                        'hidden' => $q->whereNull('deleted_at')->where('status', 'hidden'),
                        'locked' => $q->whereNull('deleted_at')->whereNotNull('locked_at'),
                        'deleted' => $q->whereNotNull('deleted_at'),
                        default => $q,
                    }),
                Filter::make('has_report')
                    ->label('Có report')
                    ->toggle()
                    ->query(fn (Builder $q): Builder => $q->where('report_count', '>', 0)),
            ])
            ->recordActions([
                Action::make('viewReports')
                    ->label('Xem báo cáo')
                    ->icon('heroicon-m-flag')
                    ->color('gray')
                    ->visible(fn (CommunityPost $p): bool => $p->report_count > 0)
                    ->modalHeading('Danh sách báo cáo')
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng')
                    ->modalContent(fn (CommunityPost $p): HtmlString => $this->reportsContent($p)),
                Action::make('lock')
                    ->label('Khóa')
                    ->icon('heroicon-m-lock-closed')
                    ->color('warning')
                    ->visible(fn (CommunityPost $p): bool => $p->deleted_at === null && $p->locked_at === null)
                    ->schema([Textarea::make('reason')->label('Lý do khóa (cư dân sẽ thấy)')->required()->rows(2)])
                    ->action(fn (CommunityPost $p, array $data) => $this->moderate($p, 'lock', $data['reason'] ?? null)),
                Action::make('unlock')
                    ->label('Mở khóa')
                    ->icon('heroicon-m-lock-open')
                    ->color('gray')
                    ->visible(fn (CommunityPost $p): bool => $p->deleted_at === null && $p->locked_at !== null)
                    ->requiresConfirmation()
                    ->action(fn (CommunityPost $p) => $this->moderate($p, 'unlock', null)),
                Action::make('hide')
                    ->label('Ẩn')
                    ->icon('heroicon-m-eye-slash')
                    ->color('danger')
                    ->visible(fn (CommunityPost $p): bool => $p->deleted_at === null && $p->status !== 'hidden')
                    ->schema([Textarea::make('reason')->label('Lý do ẩn (cư dân sẽ thấy)')->required()->rows(2)])
                    ->action(fn (CommunityPost $p, array $data) => $this->moderate($p, 'hide', $data['reason'] ?? null)),
                Action::make('unhide')
                    ->label('Bỏ ẩn')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->visible(fn (CommunityPost $p): bool => $p->deleted_at === null && $p->status === 'hidden')
                    ->requiresConfirmation()
                    ->action(fn (CommunityPost $p) => $this->moderate($p, 'unhide', null)),
                Action::make('softDelete')
                    ->label('Xóa mềm')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->visible(fn (CommunityPost $p): bool => $p->deleted_at === null)
                    ->schema([Textarea::make('reason')->label('Lý do xóa')->required()->rows(2)])
                    ->action(fn (CommunityPost $p, array $data) => $this->moderate($p, 'delete', $data['reason'] ?? null)),
                Action::make('restore')
                    ->label('Khôi phục')
                    ->icon('heroicon-m-arrow-path')
                    ->color('gray')
                    ->visible(fn (CommunityPost $p): bool => $p->deleted_at !== null)
                    ->requiresConfirmation()
                    ->action(fn (CommunityPost $p) => $this->moderate($p, 'restore', null)),
            ])
            ->emptyStateHeading('Không có bài nào khớp bộ lọc')
            ->emptyStateIcon('heroicon-o-shield-check')
            ->paginated([10, 25, 50]);
    }

    private function moderate(CommunityPost $post, string $action, ?string $reason): void
    {
        try {
            app(ModerateCommunityPostAction::class)->execute($post, $action, $reason, auth()->user());
            $this->audit('community.moderate.'.$action, "Bài #{$post->id}: {$action}".($reason ? " — {$reason}" : ''), CommunityPost::class, $post->id);
            Notification::make()->title('Đã cập nhật')->success()->send();
        } catch (InvalidArgumentException $e) {
            Notification::make()->title('Không thể thực hiện')->body($e->getMessage())->danger()->send();
        }
        $this->refreshTable();
    }

    public function resolveReport(int $reportId): void
    {
        $report = CommunityPostReport::findOrFail($reportId);
        $report->markResolved(auth()->user());
        $this->audit('community.report.resolve', "Report #{$reportId} đã xử lý (khóa/ẩn/xóa bài).", CommunityPostReport::class, $reportId);
        Notification::make()->title('Đã đóng report — đã xử lý')->success()->send();
        $this->refreshTable();
    }

    public function dismissReport(int $reportId): void
    {
        $report = CommunityPostReport::findOrFail($reportId);
        $report->markDismissed(auth()->user());
        $this->audit('community.report.dismiss', "Report #{$reportId} đã bỏ qua (không có căn cứ).", CommunityPostReport::class, $reportId);
        Notification::make()->title('Đã bỏ qua report')->success()->send();
        $this->refreshTable();
    }

    private function reportsContent(CommunityPost $post): HtmlString
    {
        $reports = $post->reports()->with('reporter')->latest()->get();

        $reasonLabel = ['spam' => 'Spam', 'offensive' => 'Xúc phạm', 'false_info' => 'Sai sự thật', 'other' => 'Khác'];
        $statusBadge = [
            'open' => '<span style="color:#d97706;font-weight:600;">● Chờ xử lý</span>',
            'resolved' => '<span style="color:#059669;font-weight:600;">● Đã xử lý</span>',
            'dismissed' => '<span style="color:#64748b;font-weight:600;">● Đã bỏ qua</span>',
        ];

        $rows = '';
        foreach ($reports as $r) {
            $rows .= '<tr style="border-top:1px solid #e2e8f0;">'
                .'<td style="padding:6px 8px;">'.e($r->reporter?->name ?? '—').'</td>'
                .'<td style="padding:6px 8px;">'.e($reasonLabel[$r->reason] ?? $r->reason).'</td>'
                .'<td style="padding:6px 8px;color:#64748b;font-size:12px;">'.e((string) $r->note).'</td>'
                .'<td style="padding:6px 8px;white-space:nowrap;">'.($statusBadge[$r->status] ?? e($r->status)).'</td>'
                .'<td style="padding:6px 8px;white-space:nowrap;">'
                .($r->status === 'open'
                    ? '<button wire:click="resolveReport('.$r->id.')" style="color:#059669;font-weight:600;margin-right:8px;">Đã xử lý</button>'
                    .'<button wire:click="dismissReport('.$r->id.')" style="color:#64748b;font-weight:600;">Bỏ qua</button>'
                    : '—')
                .'</td></tr>';
        }

        return new HtmlString(
            '<div style="max-height:52vh;overflow:auto;border:1px solid #e2e8f0;border-radius:8px;">'
            .'<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            .'<thead><tr style="background:#f8fafc;text-align:left;">'
            .'<th style="padding:6px 8px;">Người báo cáo</th><th style="padding:6px 8px;">Lý do</th>'
            .'<th style="padding:6px 8px;">Ghi chú</th><th style="padding:6px 8px;">Trạng thái</th><th style="padding:6px 8px;">Hành động</th>'
            .'</tr></thead><tbody>'.$rows.'</tbody></table></div>'
        );
    }

}
