<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesIntegrationAudit;
use App\Models\IntegrationEvent;
use App\Models\IntegrationRetryJob;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * WEB-UX-28-08 — Event Log Monitor.
 *
 * Theo dõi event tích hợp: nguồn, event type, correlation ID, tenant, duration,
 * retry, message. Lọc + chi tiết + replay (idempotent) + export (background).
 */
class EventLogMonitor extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesIntegrationAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static ?string $navigationLabel = 'Event Log';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Giám sát Event Log';

    protected static ?string $slug = 'integrations/events';

    protected string $view = 'filament.pages.integration-event-monitor';

    public const STATUS = [
        'success' => ['Thành công', 'success'], 'failed' => ['Thất bại', 'danger'],
        'warning' => ['Cảnh báo', 'warning'], 'pending' => ['Chờ', 'info'],
    ];

    protected function getViewData(): array
    {
        return [
            'kpis' => [
                ['label' => 'Tổng sự kiện', 'value' => number_format(IntegrationEvent::count()), 'accent' => 'blue'],
                ['label' => 'Thành công', 'value' => IntegrationEvent::where('status', 'success')->count(), 'accent' => 'green'],
                ['label' => 'Cảnh báo', 'value' => IntegrationEvent::where('status', 'warning')->count(), 'accent' => 'amber'],
                ['label' => 'Thất bại', 'value' => IntegrationEvent::where('status', 'failed')->count(), 'accent' => 'red'],
                ['label' => 'Đang retry', 'value' => IntegrationRetryJob::whereIn('status', ['pending', 'retrying'])->count(), 'accent' => 'amber'],
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export logs')->icon('heroicon-m-arrow-down-tray')->color('gray')
                ->requiresConfirmation()->modalDescription('Xuất event log qua tác vụ nền — sẽ thông báo khi hoàn tất.')
                ->action(function (): void {
                    $this->integrationAudit('events.export_queued', null, after: ['count' => IntegrationEvent::count()]);
                    Notification::make()->title('Đã đưa vào hàng đợi export (chạy nền)')->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(IntegrationEvent::query()->with('tenant'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('event_id')->label('Event ID')->searchable()->fontFamily('mono')->size('xs')
                    ->limit(20)->color('primary')->action($this->detailAction()),
                TextColumn::make('source')->label('Nguồn')->badge()->color('gray')->searchable(),
                TextColumn::make('event_type')->label('Loại')->searchable(),
                TextColumn::make('tenant.name')->label('Tenant')->toggleable(),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('duration_ms')->label('Thời lượng')->suffix('ms')->alignRight(),
                TextColumn::make('retry_count')->label('Retry')->alignCenter(),
                TextColumn::make('created_at')->label('Thời điểm')->since(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('source')->label('Nguồn')->options(fn () => IntegrationEvent::distinct()->orderBy('source')->pluck('source', 'source')->all()),
                Filter::make('created_at')
                    ->schema([DatePicker::make('from')->label('Từ ngày'), DatePicker::make('until')->label('Đến ngày')])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([
                $this->detailAction(),
                Action::make('replay')
                    ->label('Replay')->iconButton()->icon('heroicon-m-arrow-uturn-left')->color('warning')
                    ->requiresConfirmation()->modalDescription('Replay sự kiện? Idempotent — không tạo hiệu ứng tài chính trùng.')
                    ->action(fn (IntegrationEvent $r) => $this->replay($r)),
            ])
            ->emptyStateHeading('Chưa có sự kiện')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    public function detailAction(): Action
    {
        return Action::make('detail')
            ->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (IntegrationEvent $r) => $r->event_type)
            ->modalContent(fn (IntegrationEvent $r) => view('filament.pages.integration-event-detail', ['record' => $r]))
            ->modalWidth('2xl')->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    private function replay(IntegrationEvent $r): void
    {
        // Idempotent: reuse the original correlation id; do not duplicate a live retry job.
        $existing = IntegrationRetryJob::where('event_id', $r->event_id)->whereIn('status', ['pending', 'retrying'])->first();
        if ($existing) {
            Notification::make()->title('Sự kiện đã có job replay đang chạy (idempotent)')->warning()->send();

            return;
        }
        IntegrationRetryJob::create([
            'event_id' => $r->event_id, 'source' => $r->source, 'reason' => 'manual_replay',
            'status' => 'pending', 'attempt_no' => 0, 'max_attempts' => 5, 'next_retry_at' => now(),
        ]);
        $r->increment('retry_count');
        $this->integrationAudit('event.replayed', null, after: ['event_id' => $r->event_id, 'correlation_id' => $r->correlation_id]);
        Notification::make()->title('Đã đưa sự kiện vào hàng đợi replay')->success()->send();
    }
}
