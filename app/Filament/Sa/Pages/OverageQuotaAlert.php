<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesBillingAudit;
use App\Models\QuotaAlert;
use App\Models\SubscriptionAddon;
use App\Models\TenantSubscription;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * WEB-UX-27-06 — Cảnh báo vượt hạn mức.
 *
 * Alert sinh tự động từ usage đã khóa. Có thể assign/resolve/dismiss/convert-to-addon/convert-to-upgrade.
 * Phí ước tính + khuyến nghị luôn hiển thị. Ghi billing_audit_logs.
 */
class OverageQuotaAlert extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesBillingAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|\UnitEnum|null $navigationGroup = 'SaaS Billing';

    protected static ?string $navigationLabel = 'Cảnh báo vượt hạn';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Cảnh báo vượt hạn mức';

    protected static ?string $slug = 'billing/quota-alerts';

    protected string $view = 'filament.pages.overage-quota-alert';

    public const STATUS = [
        'open' => ['Mở', 'warning'], 'assigned' => ['Đã giao', 'info'], 'resolved' => ['Đã xử lý', 'success'],
        'dismissed' => ['Bỏ qua', 'gray'], 'converted_to_addon' => ['→ Add-on', 'success'], 'converted_to_upgrade' => ['→ Nâng gói', 'success'],
    ];

    protected function getViewData(): array
    {
        return [
            'kpis' => [
                ['label' => 'Đang mở', 'value' => QuotaAlert::where('status', 'open')->count(), 'accent' => 'amber'],
                ['label' => 'Đã giao', 'value' => QuotaAlert::where('status', 'assigned')->count(), 'accent' => 'blue'],
                ['label' => 'Đã chuyển đổi', 'value' => QuotaAlert::whereIn('status', ['converted_to_addon', 'converted_to_upgrade'])->count(), 'accent' => 'green'],
                ['label' => 'Phí ước tính (mở)', 'value' => number_format((float) QuotaAlert::where('status', 'open')->sum('estimated_fee') / 1_000_000, 1).'tr', 'accent' => 'red'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(QuotaAlert::query()->with(['tenant', 'owner']))
            ->defaultSort('estimated_fee', 'desc')
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable()->color('primary')->placeholder('—'),
                TextColumn::make('tenant.name')->label('Công ty')->searchable()->weight('medium'),
                TextColumn::make('meter_type')->label('Loại')->badge()->color('gray'),
                TextColumn::make('over_percent')->label('Vượt %')->formatStateUsing(fn ($state) => number_format((float) $state, 0).'%')
                    ->color(fn ($state) => $state >= 40 ? 'danger' : 'warning'),
                TextColumn::make('estimated_fee')->label('Phí ước tính')->money('VND'),
                TextColumn::make('recommendation')->label('Khuyến nghị')->wrap()->toggleable(),
                TextColumn::make('owner.name')->label('Phụ trách')->placeholder('—')->toggleable(),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all())->default('open'),
                SelectFilter::make('meter_type')->label('Loại')->options(fn () => QuotaAlert::distinct()->pluck('meter_type', 'meter_type')->all()),
            ])
            ->recordActions([
                Action::make('assign')->label('Giao phụ trách')->iconButton()->icon('heroicon-m-user-plus')->color('info')
                    ->visible(fn (QuotaAlert $a) => in_array($a->status, ['open', 'assigned'], true))
                    ->schema([Select::make('owner_user_id')->label('Người phụ trách')->required()->searchable()
                        ->options(fn () => User::where('account_type', 'staff')->pluck('name', 'id'))])
                    ->action(function (QuotaAlert $a, array $data): void {
                        $a->update(['owner_user_id' => $data['owner_user_id'], 'status' => 'assigned']);
                        $this->billingAudit('quota.assign', $a, null, ['owner' => $data['owner_user_id']]);
                        Notification::make()->title('Đã giao phụ trách')->success()->send();
                    }),
                Action::make('toAddon')->label('Chuyển thành add-on')->iconButton()->icon('heroicon-m-plus-circle')->color('success')
                    ->visible(fn (QuotaAlert $a) => in_array($a->status, ['open', 'assigned'], true))
                    ->requiresConfirmation()->modalDescription(fn (QuotaAlert $a) => 'Tạo add-on ~'.number_format($a->estimated_fee).'đ cho công ty này.')
                    ->action(fn (QuotaAlert $a) => $this->convertToAddon($a)),
                Action::make('toUpgrade')->label('Cơ hội nâng gói')->iconButton()->icon('heroicon-m-arrow-trending-up')->color('success')
                    ->visible(fn (QuotaAlert $a) => in_array($a->status, ['open', 'assigned'], true))
                    ->requiresConfirmation()
                    ->action(fn (QuotaAlert $a) => $this->setStatus($a, 'converted_to_upgrade', 'quota.convert_upgrade')),
                Action::make('resolve')->label('Đã xử lý')->iconButton()->icon('heroicon-m-check')->color('success')
                    ->visible(fn (QuotaAlert $a) => in_array($a->status, ['open', 'assigned'], true))
                    ->action(fn (QuotaAlert $a) => $this->setStatus($a, 'resolved', 'quota.resolve')),
                Action::make('dismiss')->label('Bỏ qua')->iconButton()->icon('heroicon-m-x-mark')->color('gray')
                    ->visible(fn (QuotaAlert $a) => in_array($a->status, ['open', 'assigned'], true))->requiresConfirmation()
                    ->action(fn (QuotaAlert $a) => $this->setStatus($a, 'dismissed', 'quota.dismiss')),
            ])
            ->emptyStateHeading('Không có cảnh báo')
            ->emptyStateIcon('heroicon-o-bell-alert')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    private function convertToAddon(QuotaAlert $a): void
    {
        $sub = TenantSubscription::where('tenant_id', $a->tenant_id)->whereIn('status', ['active', 'trial', 'pending_renewal'])->first();
        if ($sub) {
            SubscriptionAddon::create([
                'subscription_id' => $sub->id, 'addon_code' => 'ADD-'.strtoupper($a->meter_type), 'name' => 'Add-on '.$a->meter_type.' (từ cảnh báo)',
                'quantity' => 1, 'unit_price' => $a->estimated_fee, 'mrr' => $a->estimated_fee, 'wallet_type' => null, 'status' => 'active', 'start_date' => now(),
            ]);
            $sub->increment('mrr', (float) $a->estimated_fee);
            $sub->update(['arr' => $sub->mrr * 12]);
        }
        $this->setStatus($a, 'converted_to_addon', 'quota.convert_addon');
    }

    private function setStatus(QuotaAlert $a, string $status, string $action): void
    {
        $before = ['status' => $a->status];
        $a->update(['status' => $status, 'resolved_at' => in_array($status, ['resolved', 'dismissed', 'converted_to_addon', 'converted_to_upgrade'], true) ? now() : null]);
        $this->billingAudit($action, $a, $before, ['status' => $status]);
        Notification::make()->title(self::STATUS[$status][0] ?? $status)->success()->send();
    }
}
