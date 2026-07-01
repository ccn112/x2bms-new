<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesAudit;
use App\Models\Apartment;
use App\Models\GlobalUserAccount;
use App\Models\ResidentBindingRequest;
use App\Models\ResidentUnitBinding;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * WEB-UX-22-04 — Sổ đăng ký tài khoản gốc toàn hệ thống.
 *
 * Tài khoản gốc tồn tại TRƯỚC khi thành cư dân/nhân viên/nhà thầu (rule #1, AC-03).
 * SuperAdmin thấy tất cả (AC-01); HQ chỉ thấy tài khoản có yêu cầu/binding trong tenant (AC-02).
 * Khoá tài khoản bắt buộc lý do + audit. Tạo yêu cầu gắn căn từ đây.
 */
class GlobalUserRegistry extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesAudit;

    protected static function platformFeature(): ?string
    {
        return 'global_account';
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng (SuperAdmin)';

    protected static ?string $navigationLabel = 'Tài khoản gốc';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Sổ đăng ký tài khoản gốc';

    protected static ?string $slug = 'platform/user-registry';

    protected string $view = 'filament.pages.global-user-registry';

    public const IDENTITY = [
        'unverified' => ['Chưa xác thực', 'gray'],
        'phone_verified' => ['Đã xác thực SĐT', 'info'],
        'email_verified' => ['Đã xác thực email', 'info'],
        'verified' => ['Đã định danh', 'success'],
    ];

    public const TYPE = [
        'public_user' => 'Người dùng', 'resident' => 'Cư dân', 'employee' => 'Nhân viên',
        'contractor' => 'Nhà thầu', 'vendor' => 'NCC', 'platform_admin' => 'Quản trị',
    ];

    /** @return \Illuminate\Database\Eloquent\Builder<GlobalUserAccount> */
    private function scoped()
    {
        $user = Auth::user();
        $q = GlobalUserAccount::query();

        if (! $user->isPlatformAdmin()) {
            // HQ: chỉ tài khoản có yêu cầu gắn căn trong tenant của mình.
            $q->whereIn('id', ResidentBindingRequest::withoutGlobalScope('tenant')
                ->where('tenant_id', $user->tenant_id)->pluck('user_account_id'));
        }

        return $q;
    }

    protected function getViewData(): array
    {
        $base = fn () => $this->scoped();
        $boundIds = ResidentUnitBinding::withoutGlobalScope('tenant')->pluck('user_account_id')->unique();

        return [
            'kpis' => [
                ['label' => 'Tổng tài khoản', 'value' => (clone $base())->count(), 'accent' => 'blue'],
                ['label' => 'Đã định danh', 'value' => (clone $base())->where('identity_status', 'verified')->count(), 'accent' => 'green'],
                ['label' => 'Chưa gắn căn', 'value' => (clone $base())->whereNotIn('id', $boundIds)->count(), 'accent' => 'amber'],
                ['label' => 'Nghi trùng', 'value' => (clone $base())->whereNotNull('duplicate_group_id')->count(), 'accent' => 'red'],
                ['label' => 'Đã khoá', 'value' => (clone $base())->where('account_status', 'suspended')->count(), 'accent' => 'red'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->scoped()->withCount('unitBindings'))
            ->defaultSort('first_registered_at', 'desc')
            ->columns([
                TextColumn::make('full_name')->label('Họ tên')->searchable()->weight('medium')
                    ->description(fn (GlobalUserAccount $a) => $a->email),
                TextColumn::make('phone')->label('SĐT')->searchable(),
                TextColumn::make('account_type')->label('Loại')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => self::TYPE[$state] ?? $state),
                TextColumn::make('identity_status')->label('Định danh')->badge()
                    ->formatStateUsing(fn (string $state) => self::IDENTITY[$state][0] ?? $state)
                    ->color(fn (string $state) => self::IDENTITY[$state][1] ?? 'gray'),
                TextColumn::make('unit_bindings_count')->label('Số căn')->badge()->color('info')->alignCenter(),
                TextColumn::make('risk_score')->label('Risk')->badge()
                    ->color(fn (int $state) => $state >= 60 ? 'danger' : ($state >= 20 ? 'warning' : 'gray')),
                TextColumn::make('duplicate_group_id')->label('Nhóm trùng')->placeholder('—')->badge()->color('danger')->toggleable(),
                TextColumn::make('account_status')->label('TT')->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state) => $state === 'active' ? 'Hoạt động' : 'Đã khoá'),
                TextColumn::make('last_login_at')->label('Đăng nhập')->since()->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('account_type')->label('Loại tài khoản')->options(self::TYPE),
                SelectFilter::make('identity_status')->label('Định danh')
                    ->options(collect(self::IDENTITY)->map(fn ($v) => $v[0])->all()),
                Filter::make('duplicate')->label('Chỉ nghi trùng')
                    ->query(fn ($q) => $q->whereNotNull('duplicate_group_id'))->toggle(),
                Filter::make('suspended')->label('Chỉ đã khoá')
                    ->query(fn ($q) => $q->where('account_status', 'suspended'))->toggle(),
            ])
            ->recordActions([
                $this->viewAction(),
                Action::make('verify')
                    ->label('Xác nhận định danh')->iconButton()->icon('heroicon-m-shield-check')->color('success')
                    ->visible(fn (GlobalUserAccount $a) => $a->identity_status !== 'verified')
                    ->requiresConfirmation()
                    ->action(function (GlobalUserAccount $a): void {
                        $a->update(['identity_status' => 'verified']);
                        $this->audit('account.verify', 'Xác nhận định danh '.$a->full_name, GlobalUserAccount::class, $a->id);
                        Notification::make()->title('Đã xác nhận định danh')->success()->send();
                    }),
                Action::make('suspend')
                    ->label('Khoá tài khoản')->iconButton()->icon('heroicon-m-lock-closed')->color('danger')
                    ->visible(fn (GlobalUserAccount $a) => $a->account_status === 'active')
                    ->schema([Textarea::make('reason')->label('Lý do khoá')->required()->rows(3)])
                    ->action(function (GlobalUserAccount $a, array $data): void {
                        $a->update(['account_status' => 'suspended']);
                        $this->audit('account.suspend', 'Khoá tài khoản '.$a->full_name.': '.$data['reason'], GlobalUserAccount::class, $a->id);
                        Notification::make()->title('Đã khoá tài khoản')->success()->send();
                    }),
                Action::make('unsuspend')
                    ->label('Mở khoá')->iconButton()->icon('heroicon-m-lock-open')->color('warning')
                    ->visible(fn (GlobalUserAccount $a) => $a->account_status === 'suspended')
                    ->requiresConfirmation()
                    ->action(function (GlobalUserAccount $a): void {
                        $a->update(['account_status' => 'active']);
                        $this->audit('account.unsuspend', 'Mở khoá tài khoản '.$a->full_name, GlobalUserAccount::class, $a->id);
                        Notification::make()->title('Đã mở khoá')->success()->send();
                    }),
                Action::make('createBinding')
                    ->label('Tạo yêu cầu gắn căn')->iconButton()->icon('heroicon-m-home-modern')->color('info')
                    ->schema([
                        Select::make('apartment_id')->label('Căn hộ')->required()->searchable()
                            ->options(fn () => Apartment::orderBy('code')->limit(200)->pluck('code', 'id')),
                        Select::make('requested_role')->label('Vai trò')->required()
                            ->options(ResidentBindingQueue::ROLE)->default('owner'),
                    ])
                    ->action(fn (GlobalUserAccount $a, array $data) => $this->createBinding($a, $data)),
            ])
            ->emptyStateHeading('Chưa có tài khoản')
            ->emptyStateIcon('heroicon-o-users')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->label('Hồ sơ')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (GlobalUserAccount $a) => $a->full_name)
            ->modalContent(fn (GlobalUserAccount $a) => view('filament.pages.account-profile', [
                'account' => $a,
                'bindings' => ResidentUnitBinding::withoutGlobalScope('tenant')->with('apartment')->where('user_account_id', $a->id)->get(),
                'requests' => ResidentBindingRequest::withoutGlobalScope('tenant')->with('apartment')->where('user_account_id', $a->id)->latest('requested_at')->get(),
                'duplicates' => $a->duplicate_group_id
                    ? GlobalUserAccount::where('duplicate_group_id', $a->duplicate_group_id)->where('id', '!=', $a->id)->get()
                    : collect(),
                'identityMap' => self::IDENTITY,
                'typeMap' => self::TYPE,
                'roleMap' => ResidentBindingQueue::ROLE,
            ]))
            ->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    private function createBinding(GlobalUserAccount $a, array $data): void
    {
        $apt = Apartment::with('building')->find($data['apartment_id']);
        $count = ResidentBindingRequest::withoutGlobalScope('tenant')->count() + 1;

        $req = ResidentBindingRequest::create([
            'code' => 'BIND-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT),
            'user_account_id' => $a->id,
            'tenant_id' => $apt?->tenant_id,
            'project_id' => $apt?->building?->project_id,
            'building_id' => $apt?->building_id,
            'apartment_id' => $apt?->id,
            'requested_role' => $data['requested_role'],
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $this->audit('binding.create', 'Tạo yêu cầu gắn căn '.$req->code.' cho '.$a->full_name, ResidentBindingRequest::class, $req->id);
        Notification::make()->title('Đã tạo yêu cầu '.$req->code)->success()->send();
    }
}
