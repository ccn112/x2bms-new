<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesSupportAudit;
use App\Models\SupportAuditLog;
use App\Models\SupportReport;
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
 * WEB-UX-30-10 — Support Audit & Resolution Report.
 * Hiệu suất hỗ trợ, SLA compliance, data fix, rollback, CSAT, root cause + export.
 * Số liệu đọc từ support_reports (resolution) — đúng ảnh.
 */
class SupportAuditResolutionReport extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesSupportAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static ?string $navigationLabel = 'Audit & Report';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Báo cáo audit & kết quả xử lý';

    protected static ?string $slug = 'support/reports';

    protected string $view = 'filament.pages.support-audit-report';

    protected function getViewData(): array
    {
        $m = (array) (SupportReport::where('type', 'resolution')->latest()->value('metrics_json') ?? []);

        return [
            'period' => SupportReport::where('type', 'resolution')->latest()->value('period') ?? '—',
            'kpis' => [
                ['label' => 'Ticket đã xử lý', 'value' => number_format($m['tickets_resolved'] ?? 0), 'accent' => 'blue'],
                ['label' => 'MTTR', 'value' => $m['mttr'] ?? '—', 'accent' => 'blue'],
                ['label' => 'SLA compliance', 'value' => ($m['sla_compliance'] ?? 0).'%', 'accent' => 'green'],
                ['label' => 'Data fixes', 'value' => number_format($m['data_fixes'] ?? 0), 'accent' => 'amber'],
                ['label' => 'Rollbacks', 'value' => $m['rollbacks'] ?? 0, 'accent' => 'red'],
                ['label' => 'CSAT', 'value' => ($m['csat'] ?? 0).'/5', 'accent' => 'green'],
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label('Export báo cáo')->icon('heroicon-m-arrow-down-tray')->color('gray')
                ->requiresConfirmation()->modalDescription('Xuất báo cáo (SLA/MTTR/CSAT/data-fix/rollback/root-cause) qua tác vụ nền.')
                ->action(function (): void {
                    $this->supportAudit('report.export_queued', null, after: ['period' => $this->getViewData()['period']]);
                    Notification::make()->title('Đã đưa vào hàng đợi export (chạy nền)')->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SupportAuditLog::query()->with('actor'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Thời điểm')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('action')->label('Hành động')->badge()->color('gray')->searchable(),
                TextColumn::make('entity_type')->label('Đối tượng'),
                TextColumn::make('actor.name')->label('Người thực hiện')->toggleable(),
                TextColumn::make('reason')->label('Lý do')->limit(40)->toggleable(),
                TextColumn::make('ip_address')->label('IP')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('action')->label('Hành động')->options(fn () => SupportAuditLog::distinct()->orderBy('action')->pluck('action', 'action')->all()),
            ])
            ->emptyStateHeading('Chưa có audit log')
            ->striped()
            ->paginated([25, 50, 100]);
    }
}
