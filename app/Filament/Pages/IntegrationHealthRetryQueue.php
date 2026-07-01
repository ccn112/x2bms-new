<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesIntegrationAudit;
use App\Models\IntegrationConnection;
use App\Models\IntegrationCredential;
use App\Models\IntegrationEvent;
use App\Models\IntegrationIncident;
use App\Models\IntegrationRetryJob;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * WEB-UX-28-09 — Integration Health & Retry Queue.
 *
 * SLA/latency/failure rate + hàng đợi retry (retry now/skip/dead-letter) + timeline
 * sự cố + credential sắp hết hạn. Mọi hành động ghi integration_audit_logs.
 */
class IntegrationHealthRetryQueue extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesIntegrationAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static ?string $navigationLabel = 'Sức khỏe & Retry';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Sức khỏe tích hợp & Hàng đợi retry';

    protected static ?string $slug = 'integrations/health';

    protected string $view = 'filament.pages.integration-health-retry-queue';

    public const RETRY_STATUS = [
        'pending' => ['Chờ', 'warning'], 'retrying' => ['Đang thử', 'info'], 'succeeded' => ['Thành công', 'success'],
        'failed' => ['Thất bại', 'danger'], 'skipped' => ['Bỏ qua', 'gray'], 'dead_letter' => ['Dead-letter', 'danger'],
    ];

    protected function getViewData(): array
    {
        $total = max(1, IntegrationEvent::count());
        $failed = IntegrationEvent::where('status', 'failed')->count();

        return [
            'kpis' => [
                ['label' => 'Success rate (SLA)', 'value' => number_format((float) IntegrationConnection::whereNotNull('success_rate_24h')->avg('success_rate_24h'), 1).'%', 'accent' => 'green'],
                ['label' => 'Latency TB', 'value' => number_format((float) IntegrationConnection::whereNotNull('avg_latency_ms')->avg('avg_latency_ms')).'ms', 'accent' => 'blue'],
                ['label' => 'Error rate', 'value' => number_format($failed / $total * 100, 1).'%', 'accent' => 'red'],
                ['label' => 'Retry queue', 'value' => IntegrationRetryJob::whereIn('status', ['pending', 'retrying'])->count(), 'accent' => 'amber'],
                ['label' => 'Dead-letter', 'value' => IntegrationRetryJob::where('status', 'dead_letter')->count(), 'accent' => 'red'],
                ['label' => 'Secret sắp hết hạn', 'value' => IntegrationCredential::where('status', 'expiring')->count(), 'accent' => 'amber'],
            ],
            'incidents' => IntegrationIncident::orderByDesc('started_at')->limit(8)->get(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(IntegrationRetryJob::query()->with('endpoint'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('event_id')->label('Event')->fontFamily('mono')->size('xs')->limit(20)->searchable(),
                TextColumn::make('source')->label('Nguồn')->badge()->color('gray'),
                TextColumn::make('endpoint.endpoint_name')->label('Endpoint')->toggleable(),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::RETRY_STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::RETRY_STATUS[$state][1] ?? 'gray'),
                TextColumn::make('attempt_no')->label('Lần thử')->formatStateUsing(fn ($state, IntegrationRetryJob $r) => $state.'/'.$r->max_attempts)->alignCenter(),
                TextColumn::make('next_retry_at')->label('Retry kế')->since()->placeholder('—'),
                TextColumn::make('last_error')->label('Lỗi')->limit(30)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::RETRY_STATUS)->map(fn ($v) => $v[0])->all()),
            ])
            ->recordActions([
                Action::make('retryNow')
                    ->label('Retry now')->iconButton()->icon('heroicon-m-play')->color('success')
                    ->visible(fn (IntegrationRetryJob $r) => in_array($r->status, ['pending', 'retrying', 'failed'], true))
                    ->requiresConfirmation()
                    ->action(function (IntegrationRetryJob $r): void {
                        // Simulated retry: succeeds unless it has already exhausted attempts.
                        $ok = $r->attempt_no + 1 < $r->max_attempts;
                        $r->update([
                            'status' => $ok ? 'succeeded' : 'failed', 'attempt_no' => $r->attempt_no + 1,
                            'next_retry_at' => null, 'last_error' => $ok ? null : 'Retry failed',
                        ]);
                        $this->integrationAudit('retry_job.retried', $r, after: ['result' => $r->status, 'attempt' => $r->attempt_no]);
                        Notification::make()->title($ok ? 'Retry thành công' : 'Retry thất bại')->{$ok ? 'success' : 'danger'}()->send();
                    }),
                Action::make('skip')
                    ->label('Bỏ qua')->iconButton()->icon('heroicon-m-forward')->color('gray')
                    ->visible(fn (IntegrationRetryJob $r) => in_array($r->status, ['pending', 'retrying', 'failed'], true))
                    ->requiresConfirmation()
                    ->action(function (IntegrationRetryJob $r): void {
                        $r->update(['status' => 'skipped', 'next_retry_at' => null]);
                        $this->integrationAudit('retry_job.skipped', $r);
                        Notification::make()->title('Đã bỏ qua')->success()->send();
                    }),
                Action::make('deadLetter')
                    ->label('Dead-letter')->iconButton()->icon('heroicon-m-inbox-stack')->color('danger')
                    ->visible(fn (IntegrationRetryJob $r) => ! in_array($r->status, ['succeeded', 'dead_letter'], true))
                    ->requiresConfirmation()->modalDescription('Chuyển job vào dead-letter (ngừng retry tự động)?')
                    ->action(function (IntegrationRetryJob $r): void {
                        $r->update(['status' => 'dead_letter', 'next_retry_at' => null]);
                        $this->integrationAudit('retry_job.dead_letter', $r);
                        Notification::make()->title('Đã chuyển dead-letter')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Hàng đợi retry trống')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
