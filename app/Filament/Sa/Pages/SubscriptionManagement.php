<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesBillingAudit;
use App\Models\BillingInvoice;
use App\Models\Plan;
use App\Models\SubscriptionAddon;
use App\Models\TenantSubscription;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * WEB-UX-27-02/03 — Quản lý thuê bao SaaS + chi tiết.
 *
 * Vòng đời: upgrade/downgrade (đổi plan + cập nhật MRR/entitlement), thêm/bớt add-on,
 * pause/resume/suspend/renew. Mọi hành động ghi billing_audit_logs (AC).
 */
class SubscriptionManagement extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesBillingAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'SaaS Billing';

    protected static ?string $navigationLabel = 'Quản lý thuê bao';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Quản lý thuê bao SaaS';

    protected static ?string $slug = 'billing/subscriptions';

    protected string $view = 'filament.pages.subscription-management';

    public const STATUS = [
        'trial' => ['Trial', 'warning'], 'active' => ['Active', 'success'], 'pending_renewal' => ['Chờ gia hạn', 'info'],
        'past_due' => ['Quá hạn', 'danger'], 'suspended' => ['Tạm ngưng', 'danger'], 'cancelled' => ['Đã hủy', 'gray'],
    ];

    protected function getViewData(): array
    {
        $c = fn (string $state) => TenantSubscription::where('status', $state)->count();

        return [
            'kpis' => [
                ['label' => 'Active', 'value' => $c('active'), 'accent' => 'green'],
                ['label' => 'Trial', 'value' => $c('trial'), 'accent' => 'amber'],
                ['label' => 'Chờ gia hạn', 'value' => $c('pending_renewal'), 'accent' => 'blue'],
                ['label' => 'Tạm ngưng', 'value' => $c('suspended'), 'accent' => 'red'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(TenantSubscription::query()->with(['tenant', 'plan', 'contract'])->withCount('addons'))
            ->defaultSort('mrr', 'desc')
            ->columns([
                TextColumn::make('tenant.name')->label('Công ty')->searchable()->weight('medium')
                    ->description(fn (TenantSubscription $record) => $record->contract?->contract_no),
                TextColumn::make('plan.name')->label('Gói')->badge()->color('info'),
                TextColumn::make('billing_cycle')->label('Chu kỳ')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => ['monthly' => 'Tháng', 'quarterly' => 'Quý', 'yearly' => 'Năm'][$state] ?? $state),
                TextColumn::make('mrr')->label('MRR')->money('VND')->sortable(),
                TextColumn::make('addons_count')->label('Add-on')->badge()->color('gray')->alignCenter(),
                TextColumn::make('end_date')->label('Hết hạn')->date('d/m/Y')->placeholder('—')
                    ->color(fn (TenantSubscription $record) => $record->end_date && $record->end_date->isPast() ? 'danger' : 'gray'),
                TextColumn::make('auto_renew')->label('Tự GH')->badge()
                    ->formatStateUsing(fn ($v) => $v ? 'Có' : 'Không')->color(fn ($v) => $v ? 'success' : 'gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('plan_id')->label('Gói')->relationship('plan', 'name'),
            ])
            ->recordActions([
                $this->viewAction(),
                Action::make('changePlan')->label('Đổi gói (up/down)')->iconButton()->icon('heroicon-m-arrows-up-down')->color('info')
                    ->schema([Select::make('plan_id')->label('Gói mới')->required()->options(fn () => Plan::pluck('name', 'id'))])
                    ->action(fn (TenantSubscription $record, array $data) => $this->changePlan($record, (int) $data['plan_id'])),
                Action::make('addAddon')->label('Thêm add-on')->iconButton()->icon('heroicon-m-plus-circle')->color('gray')
                    ->schema([
                        TextInput::make('name')->label('Tên add-on')->required(),
                        TextInput::make('mrr')->label('MRR (đ)')->numeric()->required()->default(0),
                        Select::make('wallet_type')->label('Ví liên quan')->options(['sms' => 'SMS', 'zalo' => 'Zalo', 'email' => 'Email', 'ai_token' => 'AI token', 'storage' => 'Storage', 'api_calls' => 'API']),
                    ])
                    ->action(fn (TenantSubscription $record, array $data) => $this->addAddon($record, $data)),
                Action::make('pause')->label('Tạm dừng')->iconButton()->icon('heroicon-m-pause')->color('warning')
                    ->visible(fn (TenantSubscription $record) => $record->status === 'active')->requiresConfirmation()
                    ->action(fn (TenantSubscription $record) => $this->setStatus($record, 'suspended', 'subscription.pause')),
                Action::make('resume')->label('Kích hoạt lại')->iconButton()->icon('heroicon-m-play')->color('success')
                    ->visible(fn (TenantSubscription $record) => in_array($record->status, ['suspended', 'past_due'], true))->requiresConfirmation()
                    ->action(fn (TenantSubscription $record) => $this->setStatus($record, 'active', 'subscription.resume')),
                Action::make('renew')->label('Gia hạn')->iconButton()->icon('heroicon-m-arrow-path')->color('success')
                    ->visible(fn (TenantSubscription $record) => in_array($record->status, ['active', 'pending_renewal', 'past_due'], true))
                    ->requiresConfirmation()->modalDescription('Gia hạn thêm 1 chu kỳ theo billing_cycle.')
                    ->action(fn (TenantSubscription $record) => $this->renew($record)),
                Action::make('cancel')->label('Hủy')->iconButton()->icon('heroicon-m-x-circle')->color('danger')
                    ->visible(fn (TenantSubscription $record) => $record->status !== 'cancelled')
                    ->schema([Textarea::make('reason')->label('Lý do hủy')->required()->rows(2)])
                    ->action(fn (TenantSubscription $record, array $data) => $this->setStatus($record, 'cancelled', 'subscription.cancel', $data['reason'])),
            ])
            ->emptyStateHeading('Chưa có thuê bao')
            ->emptyStateIcon('heroicon-o-rectangle-stack')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (TenantSubscription $record) => $record->tenant?->name.' — chi tiết thuê bao')
            ->modalContent(fn (TenantSubscription $record) => view('filament.pages.subscription-detail', [
                'record' => $record->load(['tenant', 'plan', 'contract', 'items', 'addons']),
                'invoices' => BillingInvoice::where('subscription_id', $record->id)->latest('issue_date')->get(),
                'statusMap' => self::STATUS,
            ]))
            ->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    private function changePlan(TenantSubscription $record, int $planId): void
    {
        $before = ['plan_id' => $record->plan_id, 'mrr' => $record->mrr];
        $plan = Plan::find($planId);
        $newMrr = (float) ($plan->monthly_base_price ?? $record->mrr);
        $direction = $newMrr >= $record->mrr ? 'upgrade' : 'downgrade';
        $record->update(['plan_id' => $planId, 'mrr' => $newMrr, 'arr' => $newMrr * 12]);
        $this->billingAudit('subscription.'.$direction, $record, $before, ['plan_id' => $planId, 'mrr' => $newMrr]);
        Notification::make()->title($direction === 'upgrade' ? 'Đã nâng gói' : 'Đã hạ gói')->success()->send();
    }

    private function addAddon(TenantSubscription $record, array $data): void
    {
        $addon = SubscriptionAddon::create([
            'subscription_id' => $record->id, 'addon_code' => 'ADD-'.strtoupper(substr(md5($data['name'].$record->id), 0, 6)),
            'name' => $data['name'], 'quantity' => 1, 'unit_price' => $data['mrr'], 'mrr' => $data['mrr'],
            'wallet_type' => $data['wallet_type'] ?? null, 'status' => 'active', 'start_date' => now(),
        ]);
        $before = ['mrr' => $record->mrr];
        $record->increment('mrr', (float) $data['mrr']);
        $record->update(['arr' => $record->mrr * 12]);
        $this->billingAudit('subscription.add_addon', $record, $before, ['mrr' => $record->mrr, 'addon' => $addon->name]);
        Notification::make()->title('Đã thêm add-on & cập nhật MRR')->success()->send();
    }

    private function renew(TenantSubscription $record): void
    {
        $before = ['end_date' => (string) $record->end_date, 'status' => $record->status];
        $months = ['monthly' => 1, 'quarterly' => 3, 'yearly' => 12][$record->billing_cycle] ?? 1;
        $base = $record->end_date && $record->end_date->isFuture() ? $record->end_date : now();
        $record->update(['end_date' => $base->copy()->addMonths($months), 'status' => 'active']);
        $this->billingAudit('subscription.renew', $record, $before, ['end_date' => (string) $record->end_date, 'status' => 'active']);
        Notification::make()->title('Đã gia hạn thuê bao')->success()->send();
    }

    private function setStatus(TenantSubscription $record, string $status, string $action, ?string $reason = null): void
    {
        $before = ['status' => $record->status];
        $record->update(['status' => $status]);
        $this->billingAudit($action, $record, $before, ['status' => $status], $reason);
        Notification::make()->title(self::STATUS[$status][0] ?? $status)->success()->send();
    }
}
