<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * BQL-00-09 — Nhật ký hệ thống (Audit Log Viewer).
 * Read-only Filament table over audit_logs, scoped to the current project's buildings
 * (+ system-level rows). Filters date/actor/action/entity/building; row → detail modal.
 * Export gated to platform admin. No hard state — all rows come from real audit records.
 */
class AuditLogViewer extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?string $navigationLabel = 'Nhật ký hệ thống';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'Nhật ký hệ thống';

    protected static ?string $slug = 'audit-logs';

    protected string $view = 'filament.pages.audit-log-viewer';

    /** Coarse risk classification derived from the action verb (no risk column in DB). */
    private const HIGH_RISK = ['delete', 'reject', 'lock', 'unlock', 'force', 'revoke', 'publish'];

    /** @return Builder<AuditLog> */
    private function scoped(): Builder
    {
        $ctx = app(CurrentContext::class);
        $buildingIds = $ctx->buildingIds();

        return AuditLog::query()
            ->when($ctx->tenantId(), fn (Builder $q, $t) => $q->where('tenant_id', $t))
            // Include system-level rows (no building) alongside the project's buildings.
            ->where(fn (Builder $q) => $q->whereIn('building_id', $buildingIds ?: [0])->orWhereNull('building_id'));
    }

    protected function getViewData(): array
    {
        $base = fn () => $this->scoped();
        $today = (clone $base())->whereDate('created_at', now()->toDateString())->count();
        $sensitive = (clone $base())->where(function (Builder $q) {
            foreach (self::HIGH_RISK as $verb) {
                $q->orWhere('action', 'like', '%'.$verb.'%');
            }
        })->count();

        return [
            'kpis' => [
                ['label' => 'Tổng bản ghi', 'value' => number_format((clone $base())->count()), 'accent' => 'blue', 'sub' => 'Trong phạm vi dự án'],
                ['label' => 'Hôm nay', 'value' => number_format($today), 'accent' => 'teal', 'sub' => now()->format('d/m/Y')],
                ['label' => 'Thao tác nhạy cảm', 'value' => number_format($sensitive), 'accent' => 'amber', 'sub' => 'Xoá / duyệt / khoá…'],
                ['label' => 'Người thao tác', 'value' => number_format((clone $base())->distinct('user_id')->count('user_id')), 'accent' => 'green', 'sub' => 'Tài khoản khác nhau'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->scoped()->with(['user', 'building']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('actor_name')->label('Người thực hiện')->searchable()
                    ->description(fn (AuditLog $r): ?string => $r->user?->email)
                    ->placeholder('Hệ thống'),
                TextColumn::make('action')->label('Hành động')->badge()->searchable()
                    ->color(fn (string $state): string => $this->isSensitive($state) ? 'warning' : 'gray'),
                TextColumn::make('subject_type')->label('Đối tượng')
                    ->formatStateUsing(fn (?string $state, AuditLog $r): string => $state ? class_basename($state).' #'.$r->subject_id : '—')
                    ->color('gray'),
                TextColumn::make('description')->label('Mô tả')->wrap()->limit(80)->searchable(),
                TextColumn::make('building.name')->label('Tòa')->placeholder('Toàn dự án')->badge()->color('info'),
            ])
            ->filters([
                SelectFilter::make('action')->label('Hành động')
                    ->options(fn () => $this->scoped()->distinct()->orderBy('action')->pluck('action', 'action')->all())
                    ->searchable(),
                SelectFilter::make('building_id')->label('Tòa')->relationship('building', 'name'),
                Filter::make('created_at')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Từ ngày'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(fn (Builder $q, array $data): Builder => $q
                        ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Chi tiết')->icon('heroicon-m-eye')->iconButton()->color('gray')
                    ->modalHeading('Chi tiết bản ghi nhật ký')
                    ->modalSubmitAction(false)->modalCancelActionLabel('Đóng')
                    ->modalContent(fn (AuditLog $record) => view('filament.pages.partials.audit-log-detail', ['log' => $record])),
            ])
            ->toolbarActions([
                Action::make('export')
                    ->label('Xuất nhật ký')->icon('heroicon-m-arrow-down-tray')->color('gray')
                    ->visible(fn () => (bool) auth()->user()?->isPlatformAdmin())
                    ->action(fn () => Notification::make()->title('Đang chuẩn bị file xuất nhật ký…')->info()->send()),
            ])
            ->emptyStateHeading('Chưa có bản ghi nhật ký')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    private function isSensitive(string $action): bool
    {
        foreach (self::HIGH_RISK as $verb) {
            if (str_contains($action, $verb)) {
                return true;
            }
        }

        return false;
    }
}
