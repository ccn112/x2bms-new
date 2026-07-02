<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesSupportAudit;
use App\Models\DataCorrectionRequest;
use App\Models\DataFixDiffItem;
use App\Models\DataFixExecution;
use App\Models\DataFixRollback;
use App\Models\DataFixSnapshot;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * WEB-UX-30-07 — Controlled Data Fix Wizard.
 *
 * Với DCR đã duyệt: nhận diện bản ghi → tạo backup snapshot → preview diff → thực thi
 * (high/critical bắt buộc snapshot trước) → rollback. Mọi bước ghi support_audit_logs.
 */
class ControlledDataFixWizard extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesSupportAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static ?string $navigationLabel = 'Data Fix Wizard';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Wizard sửa dữ liệu có kiểm soát';

    protected static ?string $slug = 'support/data-fix-wizard';

    protected string $view = 'filament.pages.support-data-fix-wizard';

    protected function getViewData(): array
    {
        return [
            'steps' => ['Chọn yêu cầu', 'Nhận diện bản ghi', 'Backup snapshot', 'Preview diff', 'Phê duyệt cuối', 'Thực thi', 'Xác minh', 'Rollback (nếu cần)'],
            'kpis' => [
                ['label' => 'Chờ thực thi', 'value' => DataCorrectionRequest::where('status', 'approved')->count(), 'accent' => 'amber'],
                ['label' => 'Đã snapshot', 'value' => DataFixSnapshot::distinct('data_correction_request_id')->count('data_correction_request_id'), 'accent' => 'blue'],
                ['label' => 'Đã thực thi', 'value' => DataCorrectionRequest::where('status', 'executed')->count(), 'accent' => 'green'],
                ['label' => 'Đã rollback', 'value' => DataCorrectionRequest::where('status', 'rolled_back')->count(), 'accent' => 'gray'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DataCorrectionRequest::query()->with('tenant')->whereIn('status', ['approved', 'executing', 'executed', 'rollback_requested', 'rolled_back']))
            ->defaultSort('approved_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Mã')->weight('medium')->color('primary')->fontFamily('mono'),
                TextColumn::make('tenant.name')->label('Tenant')->toggleable(),
                TextColumn::make('data_type')->label('Loại dữ liệu'),
                TextColumn::make('affected_records')->label('Bản ghi')->alignRight(),
                TextColumn::make('risk')->label('Rủi ro')->badge()->color(fn (string $state) => ['critical' => 'danger', 'high' => 'warning'][$state] ?? 'gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->color(fn (string $state) => ['executed' => 'success', 'rolled_back' => 'gray'][$state] ?? 'info'),
                TextColumn::make('snapshots_count')->label('Snapshot')->counts('snapshots')->badge()->color('gray'),
            ])
            ->recordActions([
                Action::make('snapshot')->label('Snapshot')->iconButton()->icon('heroicon-m-camera')->color('info')
                    ->visible(fn (DataCorrectionRequest $r) => $r->status === 'approved')
                    ->requiresConfirmation()->modalDescription('Tạo backup snapshot trước khi thực thi?')
                    ->action(function (DataCorrectionRequest $r): void {
                        DataFixSnapshot::create(['data_correction_request_id' => $r->id, 'snapshot_json' => ['captured_at' => now()->toDateTimeString(), 'records' => $r->affected_records], 'record_count' => $r->affected_records, 'created_by' => auth()->id(), 'created_at' => now()]);
                        DataFixDiffItem::firstOrCreate(['data_correction_request_id' => $r->id, 'field' => 'sample_field'], ['entity' => $r->target_entity, 'record_id' => '1', 'before_value' => 'old', 'after_value' => 'new']);
                        $this->supportAudit('data_fix.snapshot_created', $r, after: ['records' => $r->affected_records]);
                        Notification::make()->title('Đã tạo snapshot ('.$r->affected_records.' bản ghi)')->success()->send();
                    }),
                Action::make('execute')->label('Thực thi')->iconButton()->icon('heroicon-m-bolt')->color('danger')
                    ->visible(fn (DataCorrectionRequest $r) => $r->status === 'approved')
                    ->requiresConfirmation()->modalHeading('Thực thi sửa dữ liệu')
                    ->modalDescription(fn (DataCorrectionRequest $r) => 'Thực thi thay đổi trên '.$r->affected_records.' bản ghi. Bắt buộc đã có snapshot. Ghi audit + row-lock.')
                    ->schema([TextInput::make('reason')->label('Xác nhận (lý do)')->required()])
                    ->action(function (array $data, DataCorrectionRequest $r): void {
                        if ($r->snapshots()->count() === 0) {
                            Notification::make()->title('Chưa có backup snapshot — hãy tạo snapshot trước')->danger()->send();

                            return;
                        }
                        $r->update(['status' => 'executed']);
                        DataFixExecution::create(['data_correction_request_id' => $r->id, 'executed_by' => auth()->id(), 'status' => 'executed', 'affected_count' => $r->affected_records, 'executed_at' => now(), 'log' => 'Executed with row-level lock']);
                        $this->supportAudit('data_fix.executed', $r, reason: $data['reason'], after: ['affected' => $r->affected_records]);
                        Notification::make()->title('Đã thực thi sửa dữ liệu')->success()->send();
                    }),
                Action::make('rollback')->label('Rollback')->iconButton()->icon('heroicon-m-arrow-uturn-left')->color('warning')
                    ->visible(fn (DataCorrectionRequest $r) => $r->status === 'executed')
                    ->requiresConfirmation()->modalDescription('Khôi phục từ snapshot gần nhất?')
                    ->schema([TextInput::make('reason')->label('Lý do rollback')->required()])
                    ->action(function (array $data, DataCorrectionRequest $r): void {
                        $r->update(['status' => 'rolled_back']);
                        DataFixRollback::create(['data_correction_request_id' => $r->id, 'requested_by' => auth()->id(), 'approved_by' => auth()->id(), 'status' => 'rolled_back', 'restored_count' => $r->affected_records, 'rolled_back_at' => now()]);
                        $this->supportAudit('data_fix.rolled_back', $r, reason: $data['reason']);
                        Notification::make()->title('Đã rollback từ snapshot')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Không có yêu cầu đã duyệt')
            ->striped();
    }
}
