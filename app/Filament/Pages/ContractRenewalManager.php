<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesBillingAudit;
use App\Models\SubscriptionContract;
use App\Models\SubscriptionRenewal;
use App\Models\TenantSubscription;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
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
 * WEB-UX-27-04 — Hợp đồng & gia hạn thuê bao.
 *
 * Vòng đời HĐ: draft→active→near_expiry→renewal_pending→renewed→expired→terminated.
 * Tạo task gia hạn, duyệt/từ chối, đánh dấu hết hạn, chấm dứt. Ghi billing_audit_logs.
 */
class ContractRenewalManager extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesBillingAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'SaaS Billing';

    protected static ?string $navigationLabel = 'Hợp đồng & gia hạn';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Hợp đồng & gia hạn thuê bao';

    protected static ?string $slug = 'billing/contracts';

    protected string $view = 'filament.pages.contract-renewal-manager';

    public const STATUS = [
        'draft' => ['Nháp', 'gray'], 'active' => ['Hiệu lực', 'success'], 'near_expiry' => ['Sắp hết hạn', 'warning'],
        'renewal_pending' => ['Chờ gia hạn', 'info'], 'renewed' => ['Đã gia hạn', 'success'],
        'expired' => ['Hết hạn', 'danger'], 'terminated' => ['Chấm dứt', 'gray'],
    ];

    protected function getViewData(): array
    {
        $c = fn (string $state) => SubscriptionContract::where('status', $state)->count();

        return [
            'kpis' => [
                ['label' => 'Hiệu lực', 'value' => $c('active'), 'accent' => 'green'],
                ['label' => 'Sắp hết hạn', 'value' => $c('near_expiry'), 'accent' => 'amber'],
                ['label' => 'Chờ gia hạn', 'value' => SubscriptionRenewal::whereIn('stage', ['pending', 'negotiation'])->count(), 'accent' => 'blue'],
                ['label' => 'Hết hạn', 'value' => $c('expired'), 'accent' => 'red'],
            ],
            'pipeline' => SubscriptionRenewal::with(['subscription.tenant', 'contract'])->latest()->limit(8)->get(),
            'stageMap' => ['pending' => 'Chờ xử lý', 'negotiation' => 'Đàm phán', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối', 'renewed' => 'Đã gia hạn'],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SubscriptionContract::query()->with('tenant'))
            ->defaultSort('end_date')
            ->columns([
                TextColumn::make('contract_no')->label('Số HĐ')->searchable()->color('primary')->weight('medium'),
                TextColumn::make('tenant.name')->label('Công ty')->searchable(),
                TextColumn::make('contract_type')->label('Loại')->badge()->color('gray'),
                TextColumn::make('annual_value')->label('Giá trị năm')->money('VND')->toggleable(),
                TextColumn::make('start_date')->label('Bắt đầu')->date('d/m/Y')->toggleable(),
                TextColumn::make('end_date')->label('Kết thúc')->date('d/m/Y')
                    ->color(fn (SubscriptionContract $ct) => $ct->end_date && $ct->end_date->isPast() ? 'danger' : 'gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
            ])
            ->recordActions([
                Action::make('createRenewal')->label('Tạo gia hạn')->iconButton()->icon('heroicon-m-arrow-path')->color('info')
                    ->schema([
                        DatePicker::make('target_date')->label('Ngày mục tiêu')->required(),
                        TextInput::make('proposed_value')->label('Giá trị đề xuất (đ)')->numeric()->default(0),
                        Textarea::make('note')->label('Ghi chú')->rows(2),
                    ])
                    ->action(function (SubscriptionContract $ct, array $data): void {
                        $sub = TenantSubscription::where('contract_id', $ct->id)->first();
                        $r = SubscriptionRenewal::create([
                            'subscription_id' => $sub?->id, 'contract_id' => $ct->id, 'stage' => 'pending',
                            'target_date' => $data['target_date'], 'proposed_value' => $data['proposed_value'] ?? 0, 'note' => $data['note'] ?? null,
                        ]);
                        $ct->update(['status' => 'renewal_pending']);
                        $this->billingAudit('contract.create_renewal', $ct, ['status' => $ct->getOriginal('status')], ['status' => 'renewal_pending', 'renewal_id' => $r->id]);
                        Notification::make()->title('Đã tạo task gia hạn')->success()->send();
                    }),
                Action::make('markExpired')->label('Đánh dấu hết hạn')->iconButton()->icon('heroicon-m-clock')->color('warning')
                    ->visible(fn (SubscriptionContract $ct) => ! in_array($ct->status, ['expired', 'terminated'], true))
                    ->requiresConfirmation()
                    ->action(fn (SubscriptionContract $ct) => $this->setStatus($ct, 'expired', 'contract.mark_expired')),
                Action::make('terminate')->label('Chấm dứt')->iconButton()->icon('heroicon-m-x-circle')->color('danger')
                    ->visible(fn (SubscriptionContract $ct) => $ct->status !== 'terminated')
                    ->schema([Textarea::make('reason')->label('Lý do chấm dứt')->required()->rows(2)])
                    ->action(fn (SubscriptionContract $ct, array $data) => $this->setStatus($ct, 'terminated', 'contract.terminate', $data['reason'])),
            ])
            ->emptyStateHeading('Chưa có hợp đồng')
            ->emptyStateIcon('heroicon-o-document-text')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    /** wire:click từ blade pipeline — duyệt/từ chối gia hạn. */
    public function decideRenewal(int $id, string $decision): void
    {
        $r = SubscriptionRenewal::with('contract', 'subscription')->find($id);
        if (! $r) {
            return;
        }
        if ($decision === 'approve') {
            $r->update(['stage' => 'renewed']);
            $r->contract?->update(['status' => 'renewed']);
            if ($sub = $r->subscription) {
                $months = ['monthly' => 1, 'quarterly' => 3, 'yearly' => 12][$sub->billing_cycle] ?? 12;
                $base = $sub->end_date && $sub->end_date->isFuture() ? $sub->end_date : now();
                $sub->update(['end_date' => $base->copy()->addMonths($months), 'status' => 'active']);
            }
            if ($r->contract) {
                $this->billingAudit('contract.renew', $r->contract, null, ['stage' => 'renewed']);
            }
            Notification::make()->title('Đã duyệt gia hạn')->success()->send();
        } else {
            $r->update(['stage' => 'rejected']);
            $r->contract?->update(['status' => 'active']);
            if ($r->contract) {
                $this->billingAudit('contract.renew_reject', $r->contract, null, ['stage' => 'rejected']);
            }
            Notification::make()->title('Đã từ chối gia hạn')->warning()->send();
        }
    }

    private function setStatus(SubscriptionContract $ct, string $status, string $action, ?string $reason = null): void
    {
        $before = ['status' => $ct->status];
        $ct->update(['status' => $status]);
        $this->billingAudit($action, $ct, $before, ['status' => $status], $reason);
        Notification::make()->title(self::STATUS[$status][0] ?? $status)->success()->send();
    }
}
