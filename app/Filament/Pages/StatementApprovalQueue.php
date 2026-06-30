<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ProvidesAiContext;
use App\Models\AuditLog;
use App\Models\BillingRun;
use App\Models\User;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/**
 * WEB-FORM-07-04 — Duyệt bảng kê hàng loạt.
 * Approves at BATCH level (billing_runs = one bảng kê per tòa/kỳ). Themed /admin
 * queue with KPI cards, filter + bulk Duyệt/Từ chối/Yêu cầu bổ sung/Phân công,
 * an approval-flow strip and read-only context panels. AI chat = global floating FAB.
 */
class StatementApprovalQueue extends Page implements HasTable
{
    use InteractsWithTable;
    use ProvidesAiContext;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|\UnitEnum|null $navigationGroup = 'Tài chính – Phí';

    protected static ?string $navigationLabel = 'Duyệt bảng kê';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Duyệt bảng kê hàng loạt';

    protected static ?string $slug = 'finance/statement-approvals';

    protected string $view = 'filament.pages.statement-approval-queue';

    public const STATUS = [
        'draft' => ['Nháp', 'gray'],
        'pending' => ['Chờ duyệt', 'warning'],
        'reviewing' => ['Đang rà soát', 'info'],
        'approved' => ['Đã duyệt', 'success'],
        'published' => ['Đã phát hành', 'success'],
        'rejected' => ['Bị từ chối', 'danger'],
        'need_more' => ['Cần bổ sung', 'warning'],
    ];

    /** @return \Illuminate\Database\Eloquent\Builder<BillingRun> */
    private function scopedRuns()
    {
        $buildingIds = app(CurrentContext::class)->buildingIds() ?: [0];

        return BillingRun::query()->whereIn('building_id', $buildingIds);
    }

    protected function getViewData(): array
    {
        $sum = fn (string $status) => (clone $this->scopedRuns())->where('approval_status', $status);

        $topPending = (clone $this->scopedRuns())->where('approval_status', 'pending')
            ->with(['building'])->orderByDesc('total_billed')->limit(3)->get();

        // Feed this screen's AI context into the shared floating chat (FAB).
        $this->shareAiContext([
            'title' => 'Gợi ý ưu tiên duyệt',
            'suggestions' => $topPending->map(fn (BillingRun $r) => [
                'title' => $r->code,
                'amount' => number_format($r->total_billed).' đ',
                'sub' => $r->building?->name.' · '.$r->apartment_count.' căn',
            ])->all(),
        ]);

        return [
            'kpis' => [
                ['label' => 'Chờ duyệt', 'value' => (clone $sum('pending'))->count(), 'accent' => 'amber', 'sub' => number_format((clone $sum('pending'))->sum('total_billed')).' đ'],
                ['label' => 'Đã duyệt', 'value' => (clone $sum('approved'))->count(), 'accent' => 'green', 'sub' => number_format((clone $sum('approved'))->sum('total_billed')).' đ'],
                ['label' => 'Cần bổ sung', 'value' => (clone $sum('need_more'))->count(), 'accent' => 'amber'],
                ['label' => 'Bị từ chối', 'value' => (clone $sum('rejected'))->count(), 'accent' => 'red'],
            ],
            'history' => AuditLog::whereIn('action', ['billing.approve', 'billing.reject', 'billing.need_more', 'billing.assign', 'billing.publish'])
                ->latest()->limit(5)->get(),
            'flow' => [
                ['Tạo bảng kê', 'Nhân viên kế toán tạo', 'done'],
                ['Rà soát', 'Kế toán trưởng rà soát', 'done'],
                ['Chờ duyệt', 'BQL/CĐT duyệt', 'current'],
                ['Duyệt cuối', 'Ký duyệt cuối cùng', 'todo'],
                ['Phát hành', 'Gửi cư dân/khách thuê', 'todo'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->scopedRuns()->with(['building', 'billingPeriod', 'creator', 'approver']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Mã bảng kê')->searchable()->sortable()->color('primary'),
                TextColumn::make('billingPeriod.code')->label('Kỳ thu')->badge()->color('gray'),
                TextColumn::make('building.name')->label('Tòa nhà')->searchable(),
                TextColumn::make('apartment_count')->label('Số căn')->numeric()->sortable(),
                TextColumn::make('total_billed')->label('Tổng tiền')->money('VND')->sortable(),
                TextColumn::make('creator.name')->label('Người tạo')->placeholder('—')
                    ->description(fn (BillingRun $r): ?string => $r->created_at?->format('d/m/Y H:i')),
                TextColumn::make('approver.name')->label('Người duyệt')->placeholder('Chưa phân công'),
                TextColumn::make('approval_status')
                    ->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state): string => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('sla_due_at')->label('SLA')
                    ->formatStateUsing(fn ($state): string => $state ? $state->diffForHumans() : '—')
                    ->color(fn (BillingRun $r): string => $r->sla_due_at && $r->sla_due_at->isPast() ? 'danger' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('approval_status')
                    ->label('Trạng thái')
                    ->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Duyệt')->iconButton()->icon('heroicon-m-check')->color('success')
                    ->visible(fn (BillingRun $r) => in_array($r->approval_status, ['pending', 'reviewing', 'need_more']))
                    ->requiresConfirmation()
                    ->action(fn (BillingRun $r) => $this->approve(collect([$r]))),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Duyệt')->icon('heroicon-m-check-circle')->color('success')
                        ->requiresConfirmation()->modalHeading('Duyệt bảng kê đã chọn')
                        ->action(fn (Collection $records) => $this->approve($records))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('reject')
                        ->label('Từ chối')->icon('heroicon-m-x-circle')->color('danger')
                        ->schema([Textarea::make('note')->label('Lý do từ chối')->required()->rows(3)])
                        ->action(fn (Collection $records, array $data) => $this->transitionRuns($records, 'rejected', 'billing.reject', 'Từ chối', $data['note'] ?? null))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('need_more')
                        ->label('Yêu cầu bổ sung')->icon('heroicon-m-exclamation-circle')->color('warning')
                        ->schema([Textarea::make('note')->label('Nội dung cần bổ sung')->required()->rows(3)])
                        ->action(fn (Collection $records, array $data) => $this->transitionRuns($records, 'need_more', 'billing.need_more', 'Yêu cầu bổ sung', $data['note'] ?? null))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('assign')
                        ->label('Phân công người duyệt')->icon('heroicon-m-user-plus')->color('gray')
                        ->schema([
                            Select::make('approver_id')->label('Người duyệt')
                                ->options(fn () => User::where('is_platform_admin', true)->orWhereHas('roles')->pluck('name', 'id'))
                                ->searchable()->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each->update(['approver_id' => $data['approver_id']]);
                            $this->audit('billing.assign', 'Phân công người duyệt cho '.$records->count().' bảng kê');
                            Notification::make()->title('Đã phân công '.$records->count().' bảng kê')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('export')
                        ->label('Xuất Excel')->icon('heroicon-m-arrow-down-tray')->color('gray')
                        ->action(fn () => Notification::make()->title('Đang chuẩn bị file xuất…')->info()->send()),
                ]),
            ])
            ->emptyStateHeading('Không có bảng kê')
            ->emptyStateIcon('heroicon-o-document-text')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    private function approve(Collection $records): void
    {
        $eligible = $records->whereIn('approval_status', ['pending', 'reviewing', 'need_more']);
        $eligible->each->update(['approval_status' => 'approved', 'approver_id' => auth()->id()]);
        $this->audit('billing.approve', 'Duyệt '.$eligible->count().' bảng kê');
        Notification::make()->title('Đã duyệt '.$eligible->count().' bảng kê')->success()->send();
    }

    private function transitionRuns(Collection $records, string $status, string $action, string $verb, ?string $note): void
    {
        $records->each->update(['approval_status' => $status, 'approval_note' => $note]);
        $this->audit($action, $verb.' '.$records->count().' bảng kê'.($note ? ': '.$note : ''));
        Notification::make()->title($verb.' '.$records->count().' bảng kê')->warning()->send();
    }

    private function audit(string $action, string $description): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'building_id' => $user->building_id,
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
