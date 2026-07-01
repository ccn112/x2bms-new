<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesSupportAudit;
use App\Models\DataCorrectionRequest;
use App\Models\SupportTicket;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * WEB-UX-30-06 — Data Correction Request.
 * Danh sách yêu cầu sửa dữ liệu có kiểm soát (title click → chi tiết), tạo mới
 * (RichEditor lý do), duyệt/từ chối. High/critical cần 2 người duyệt. Audit đầy đủ.
 */
class DataCorrectionRequests extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesSupportAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static ?string $navigationLabel = 'Data Correction';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Yêu cầu sửa dữ liệu';

    protected static ?string $slug = 'support/data-corrections';

    protected string $view = 'filament.pages.support-data-correction';

    public const STATUS = [
        'draft' => ['Nháp', 'gray'], 'pending_approval' => ['Chờ duyệt', 'warning'], 'approved' => ['Đã duyệt', 'info'],
        'rejected' => ['Từ chối', 'danger'], 'executing' => ['Đang chạy', 'warning'], 'executed' => ['Đã thực thi', 'success'],
        'rollback_requested' => ['Chờ rollback', 'warning'], 'rolled_back' => ['Đã rollback', 'gray'], 'cancelled' => ['Đã hủy', 'gray'],
    ];

    public const RISK = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];

    protected function getViewData(): array
    {
        return [
            'kpis' => [
                ['label' => 'Tổng yêu cầu', 'value' => DataCorrectionRequest::count(), 'accent' => 'blue'],
                ['label' => 'Chờ duyệt', 'value' => DataCorrectionRequest::where('status', 'pending_approval')->count(), 'accent' => 'amber'],
                ['label' => 'Đã duyệt', 'value' => DataCorrectionRequest::where('status', 'approved')->count(), 'accent' => 'info'],
                ['label' => 'Đã thực thi', 'value' => DataCorrectionRequest::where('status', 'executed')->count(), 'accent' => 'green'],
                ['label' => 'Rủi ro cao', 'value' => DataCorrectionRequest::whereIn('risk', ['high', 'critical'])->count(), 'accent' => 'red'],
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')->label('Tạo yêu cầu')->icon('heroicon-m-plus')->color('primary')->modalWidth('2xl')
                ->schema([
                    Select::make('tenant_id')->label('Tenant')->options(Tenant::orderBy('name')->pluck('name', 'id'))->searchable(),
                    Select::make('support_ticket_id')->label('Ticket liên kết')->options(SupportTicket::latest()->limit(50)->pluck('ticket_no', 'id'))->searchable(),
                    TextInput::make('data_type')->label('Loại dữ liệu')->required(),
                    TextInput::make('target_entity')->label('Bảng/Entity')->default('residents'),
                    TextInput::make('affected_records')->label('Số bản ghi ảnh hưởng')->numeric()->default(0),
                    Select::make('risk')->label('Rủi ro')->options(self::RISK)->default('medium')->required(),
                    RichEditor::make('reason')->label('Lý do')->columnSpanFull(),
                    RichEditor::make('rollback_plan')->label('Kế hoạch rollback')->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $dcr = DataCorrectionRequest::create($data + [
                        'code' => 'DCR-'.now()->format('Y').'-'.strtoupper(Str::random(4)), 'status' => 'pending_approval', 'requested_by' => auth()->id(),
                    ]);
                    $this->supportAudit('data_correction.created', $dcr, after: ['code' => $dcr->code, 'risk' => $dcr->risk]);
                    Notification::make()->title('Đã tạo yêu cầu '.$dcr->code)->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DataCorrectionRequest::query()->with(['tenant', 'requestedBy']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable()->weight('medium')->color('primary')->fontFamily('mono')->action($this->detailAction()),
                TextColumn::make('tenant.name')->label('Tenant')->toggleable(),
                TextColumn::make('data_type')->label('Loại dữ liệu'),
                TextColumn::make('affected_records')->label('Bản ghi')->alignRight(),
                TextColumn::make('risk')->label('Rủi ro')->badge()
                    ->formatStateUsing(fn (string $state) => self::RISK[$state] ?? $state)
                    ->color(fn (string $state) => ['critical' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'gray'][$state] ?? 'gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('requestedBy.name')->label('Người yêu cầu')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('risk')->label('Rủi ro')->options(self::RISK),
            ])
            ->recordActions([
                $this->detailAction(),
                Action::make('approve')->label('Duyệt')->iconButton()->icon('heroicon-m-check')->color('success')
                    ->visible(fn (DataCorrectionRequest $r) => $r->status === 'pending_approval')
                    ->requiresConfirmation()
                    ->modalDescription(fn (DataCorrectionRequest $r) => in_array($r->risk, ['high', 'critical'], true) ? 'Rủi ro cao — cần phê duyệt 2 người. Ghi nhận phê duyệt của bạn.' : 'Duyệt yêu cầu này?')
                    ->schema([TextInput::make('reason')->label('Ghi chú duyệt')])
                    ->action(function (array $data, DataCorrectionRequest $r): void {
                        $r->approvals()->create(['approver_id' => auth()->id(), 'decision' => 'approved', 'reason' => $data['reason'] ?? null, 'approved_at' => now()]);
                        $needed = in_array($r->risk, ['high', 'critical'], true) ? 2 : 1;
                        if ($r->approvals()->where('decision', 'approved')->count() >= $needed) {
                            $r->update(['status' => 'approved', 'approver_id' => auth()->id(), 'approved_at' => now()]);
                        }
                        $this->supportAudit('data_correction.approved', $r, reason: $data['reason'] ?? null);
                        Notification::make()->title($r->fresh()->status === 'approved' ? 'Đã duyệt' : 'Đã ghi nhận (chờ đủ 2 người duyệt)')->success()->send();
                    }),
                Action::make('reject')->label('Từ chối')->iconButton()->icon('heroicon-m-x-mark')->color('danger')
                    ->visible(fn (DataCorrectionRequest $r) => in_array($r->status, ['pending_approval', 'approved'], true))
                    ->schema([TextInput::make('reason')->label('Lý do')->required()])
                    ->action(function (array $data, DataCorrectionRequest $r): void {
                        $r->update(['status' => 'rejected']);
                        $r->approvals()->create(['approver_id' => auth()->id(), 'decision' => 'rejected', 'reason' => $data['reason'], 'approved_at' => now()]);
                        $this->supportAudit('data_correction.rejected', $r, reason: $data['reason']);
                        Notification::make()->title('Đã từ chối')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có yêu cầu')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function detailAction(): Action
    {
        return Action::make('detail')->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (DataCorrectionRequest $r) => $r->code)
            ->modalContent(fn (DataCorrectionRequest $r) => view('filament.pages.support-data-correction-detail', [
                'record' => $r->load(['tenant', 'requestedBy', 'approver', 'affectedRecords', 'diffItems', 'approvals.approver', 'snapshots', 'executions']),
            ]))
            ->modalWidth('3xl')->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }
}
