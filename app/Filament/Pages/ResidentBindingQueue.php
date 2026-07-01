<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesAudit;
use App\Models\GlobalUserAccount;
use App\Models\ResidentBindingRequest;
use App\Models\ResidentUnitBinding;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * WEB-UX-22-05 — Hàng đợi duyệt gắn tài khoản gốc vào căn hộ/tòa.
 *
 * Nghiệp vụ (rule #1): tài khoản gốc (global_user_accounts) chỉ TRỞ THÀNH cư dân
 * sau khi yêu cầu gắn căn được duyệt → tạo resident_unit_binding (AC-05).
 * Duyệt/từ chối/yêu cầu bổ sung/phân công đều ghi audit (AC-08). Từ chối bắt buộc lý do (AC-06).
 * Một tài khoản có thể gắn nhiều căn/dự án (AC-07). Cảnh báo trùng SĐT/email/căn trước khi duyệt.
 */
class ResidentBindingQueue extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesAudit;

    protected static function platformFeature(): ?string
    {
        return 'resident_binding';
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng (SuperAdmin)';

    protected static ?string $navigationLabel = 'Duyệt gắn căn hộ';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Hàng đợi duyệt gắn tài khoản ↔ căn hộ';

    protected static ?string $slug = 'platform/binding-queue';

    protected string $view = 'filament.pages.resident-binding-queue';

    public const STATUS = [
        'pending' => ['Chờ duyệt', 'warning'],
        'need_more_info' => ['Cần bổ sung', 'info'],
        'approved' => ['Đã duyệt', 'success'],
        'rejected' => ['Từ chối', 'danger'],
        'cancelled' => ['Đã hủy', 'gray'],
    ];

    public const ROLE = [
        'owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'family_member' => 'Thành viên', 'guest' => 'Khách',
    ];

    /** @return \Illuminate\Database\Eloquent\Builder<ResidentBindingRequest> */
    private function scoped()
    {
        $user = Auth::user();
        // Bỏ global scope tenant (màn platform): SuperAdmin thấy tất; HQ tự lọc theo tenant.
        $q = ResidentBindingRequest::withoutGlobalScope('tenant');

        if (! $user->isPlatformAdmin()) {
            $q->where('tenant_id', $user->tenant_id);
        }

        return $q;
    }

    protected function getViewData(): array
    {
        $base = fn () => $this->scoped();
        $count = fn (string $s) => (clone $base())->where('status', $s)->count();

        return [
            'kpis' => [
                ['label' => 'Chờ duyệt', 'value' => $count('pending'), 'accent' => 'amber'],
                ['label' => 'Cần bổ sung', 'value' => $count('need_more_info'), 'accent' => 'blue'],
                ['label' => 'Đã duyệt', 'value' => $count('approved'), 'accent' => 'green'],
                ['label' => 'Từ chối', 'value' => $count('rejected'), 'accent' => 'red'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->scoped()->with(['account', 'apartment', 'reviewer']))
            ->defaultSort('requested_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Mã YC')->searchable()->color('primary')->weight('medium'),
                TextColumn::make('account.full_name')->label('Tài khoản')->searchable()
                    ->description(fn (ResidentBindingRequest $r) => $r->account?->phone),
                TextColumn::make('apartment.code')->label('Căn hộ')->placeholder('—')->badge()->color('gray'),
                TextColumn::make('requested_role')->label('Vai trò')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => self::ROLE[$state] ?? $state),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('requested_at')->label('Gửi lúc')->dateTime('d/m/Y')->sortable(),
                TextColumn::make('reviewer.name')->label('Người duyệt')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')
                    ->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all())
                    ->default('pending'),
                SelectFilter::make('requested_role')->label('Vai trò')->options(self::ROLE),
            ])
            ->recordActions([
                $this->viewAction(),
                Action::make('approve')
                    ->label('Duyệt')->iconButton()->icon('heroicon-m-check-circle')->color('success')
                    ->visible(fn (ResidentBindingRequest $r) => in_array($r->status, ['pending', 'need_more_info'], true))
                    ->requiresConfirmation()
                    ->modalHeading('Duyệt gắn căn hộ')
                    ->modalDescription(fn (ResidentBindingRequest $r) => $this->duplicateWarning($r) ?: 'Tạo liên kết cư dân ↔ căn hộ cho tài khoản này.')
                    ->schema([Textarea::make('review_note')->label('Ghi chú')->rows(2)])
                    ->action(fn (ResidentBindingRequest $r, array $data) => $this->approve($r, $data['review_note'] ?? null)),
                Action::make('needMore')
                    ->label('Yêu cầu bổ sung')->iconButton()->icon('heroicon-m-information-circle')->color('info')
                    ->visible(fn (ResidentBindingRequest $r) => $r->status === 'pending')
                    ->schema([Textarea::make('review_note')->label('Nội dung cần bổ sung')->required()->rows(3)])
                    ->action(fn (ResidentBindingRequest $r, array $data) => $this->setStatus($r, 'need_more_info', $data['review_note'])),
                Action::make('reject')
                    ->label('Từ chối')->iconButton()->icon('heroicon-m-x-circle')->color('danger')
                    ->visible(fn (ResidentBindingRequest $r) => in_array($r->status, ['pending', 'need_more_info'], true))
                    ->schema([Textarea::make('review_note')->label('Lý do từ chối')->required()->rows(3)])
                    ->action(fn (ResidentBindingRequest $r, array $data) => $this->setStatus($r, 'rejected', $data['review_note'])),
                Action::make('assign')
                    ->label('Phân công duyệt')->iconButton()->icon('heroicon-m-user-plus')->color('gray')
                    ->schema([Select::make('reviewed_by')->label('Người phụ trách')->required()->searchable()
                        ->options(fn () => User::where('account_type', 'staff')->pluck('name', 'id'))])
                    ->action(function (ResidentBindingRequest $r, array $data): void {
                        $r->update(['reviewed_by' => $data['reviewed_by']]);
                        $this->audit('binding.assign', 'Phân công duyệt '.$r->code, ResidentBindingRequest::class, $r->id);
                        Notification::make()->title('Đã phân công')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Không có yêu cầu gắn căn')
            ->emptyStateIcon('heroicon-o-identification')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (ResidentBindingRequest $r) => $r->code.' — '.($r->account?->full_name ?? ''))
            ->modalContent(fn (ResidentBindingRequest $r) => view('filament.pages.binding-detail', [
                'record' => $r->load(['account', 'apartment', 'reviewer']),
                'duplicates' => $this->duplicateAccounts($r),
                'previousBindings' => $r->account
                    ? ResidentUnitBinding::withoutGlobalScope('tenant')->with('apartment')->where('user_account_id', $r->user_account_id)->get()
                    : collect(),
                'statusMap' => self::STATUS,
                'roleMap' => self::ROLE,
            ]))
            ->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    private function approve(ResidentBindingRequest $r, ?string $note): void
    {
        $r->update([
            'status' => 'approved', 'reviewed_by' => Auth::id(), 'reviewed_at' => now(),
            'review_note' => $note ?? $r->review_note,
        ]);

        // AC-05: duyệt → tạo liên kết cư dân ↔ căn hộ (không tạo trùng).
        $binding = ResidentUnitBinding::withoutGlobalScope('tenant')->firstOrCreate(
            ['approved_request_id' => $r->id],
            [
                'user_account_id' => $r->user_account_id, 'tenant_id' => $r->tenant_id, 'project_id' => $r->project_id,
                'building_id' => $r->building_id, 'apartment_id' => $r->apartment_id,
                'role' => $r->requested_role, 'status' => 'active', 'starts_at' => now(),
            ]
        );

        // Tài khoản gốc chuyển sang loại 'resident' sau lần gắn đầu.
        if ($r->account && $r->account->account_type === 'public_user') {
            $r->account->update(['account_type' => 'resident']);
        }

        $this->audit('binding.approve', 'Duyệt gắn căn '.$r->code.' (binding #'.$binding->id.')', ResidentBindingRequest::class, $r->id);
        Notification::make()->title('Đã duyệt & tạo liên kết cư dân')->success()->send();
    }

    private function setStatus(ResidentBindingRequest $r, string $status, string $note): void
    {
        $r->update(['status' => $status, 'reviewed_by' => Auth::id(), 'reviewed_at' => now(), 'review_note' => $note]);
        $action = $status === 'rejected' ? 'binding.reject' : 'binding.need_more';
        $this->audit($action, self::STATUS[$status][0].' '.$r->code, ResidentBindingRequest::class, $r->id);
        Notification::make()->title(self::STATUS[$status][0])->success()->send();
    }

    /** Tài khoản khác trùng SĐT/email (nghi trùng người) — dùng cho cảnh báo trước duyệt. */
    private function duplicateAccounts(ResidentBindingRequest $r): \Illuminate\Support\Collection
    {
        $acc = $r->account;
        if (! $acc) {
            return collect();
        }

        return GlobalUserAccount::where('id', '!=', $acc->id)
            ->where(function ($q) use ($acc) {
                $q->when($acc->phone, fn ($qq) => $qq->orWhere('phone', $acc->phone))
                    ->when($acc->email, fn ($qq) => $qq->orWhere('email', $acc->email))
                    ->when($acc->duplicate_group_id, fn ($qq) => $qq->orWhere('duplicate_group_id', $acc->duplicate_group_id));
            })->get();
    }

    private function duplicateWarning(ResidentBindingRequest $r): ?string
    {
        $dups = $this->duplicateAccounts($r)->count();
        $unitTaken = $r->apartment_id && ResidentUnitBinding::withoutGlobalScope('tenant')->where('apartment_id', $r->apartment_id)
            ->where('status', 'active')->where('user_account_id', '!=', $r->user_account_id)->exists();

        $w = [];
        if ($dups > 0) {
            $w[] = "⚠ {$dups} tài khoản nghi trùng (SĐT/email).";
        }
        if ($unitTaken) {
            $w[] = '⚠ Căn hộ đã có chủ sở hữu đang hoạt động.';
        }

        return $w ? implode(' ', $w) : null;
    }
}
