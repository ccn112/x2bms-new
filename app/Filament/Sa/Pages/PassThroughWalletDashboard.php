<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesBillingAudit;
use App\Models\PassThroughTransaction;
use App\Models\PassThroughWallet;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * WEB-UX-27-09 — Ví pass-through & chi phí dịch vụ (SMS/Zalo/Email/AI token...).
 *
 * Nạp (có thể cần duyệt), trừ theo usage kênh, cấu hình auto top-up, cảnh báo số dư thấp.
 * Mọi giao dịch ghi pass_through_transactions + billing_audit_logs.
 */
class PassThroughWalletDashboard extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesBillingAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static string|\UnitEnum|null $navigationGroup = 'SaaS Billing';

    protected static ?string $navigationLabel = 'Ví pass-through';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Ví pass-through & chi phí dịch vụ';

    protected static ?string $slug = 'billing/wallets';

    protected string $view = 'filament.pages.pass-through-wallet-dashboard';

    public const TYPE = [
        'sms' => 'SMS', 'zalo' => 'Zalo', 'email' => 'Email', 'ai_token' => 'AI token',
        'payment_fee' => 'Phí thanh toán', 'e_invoice' => 'Hóa đơn ĐT', 'api_calls' => 'API', 'storage' => 'Storage',
    ];

    protected function getViewData(): array
    {
        $low = PassThroughWallet::whereColumn('balance', '<=', 'low_balance_threshold')->count();

        return [
            'kpis' => [
                ['label' => 'Tổng ví', 'value' => PassThroughWallet::count(), 'accent' => 'blue'],
                ['label' => 'Tổng số dư', 'value' => number_format((float) PassThroughWallet::sum('balance') / 1_000_000, 1).'tr', 'accent' => 'green'],
                ['label' => 'Số dư thấp', 'value' => $low, 'accent' => $low > 0 ? 'red' : 'green'],
                ['label' => 'Auto top-up bật', 'value' => PassThroughWallet::where('auto_topup_enabled', true)->count(), 'accent' => 'blue'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PassThroughWallet::query()->with('tenant'))
            ->defaultSort('balance', 'desc')
            ->columns([
                TextColumn::make('tenant.name')->label('Công ty')->searchable()->weight('medium'),
                TextColumn::make('wallet_type')->label('Loại ví')->badge()->color('info')
                    ->formatStateUsing(fn (string $state) => self::TYPE[$state] ?? $state),
                TextColumn::make('balance')->label('Số dư')->money('VND')->sortable()
                    ->color(fn (PassThroughWallet $record) => $record->balance <= $record->low_balance_threshold ? 'danger' : 'success'),
                TextColumn::make('monthly_target')->label('Mục tiêu tháng')->money('VND')->toggleable(),
                TextColumn::make('low_balance_threshold')->label('Ngưỡng thấp')->money('VND')->toggleable(),
                IconColumn::make('auto_topup_enabled')->label('Auto')->boolean()->alignCenter(),
                TextColumn::make('status')->label('TT')->badge()->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('wallet_type')->label('Loại ví')->options(self::TYPE),
            ])
            ->recordActions([
                Action::make('topup')->label('Nạp tiền')->iconButton()->icon('heroicon-m-plus-circle')->color('success')
                    ->schema([TextInput::make('amount')->label('Số tiền (đ)')->numeric()->required()])
                    ->action(fn (PassThroughWallet $record, array $data) => $this->topUp($record, (float) $data['amount'], 'confirmed', 'wallet.topup')),
                Action::make('requestTopup')->label('Yêu cầu nạp (chờ duyệt)')->iconButton()->icon('heroicon-m-clock')->color('warning')
                    ->schema([TextInput::make('amount')->label('Số tiền (đ)')->numeric()->required()])
                    ->action(function (PassThroughWallet $record, array $data): void {
                        PassThroughTransaction::create([
                            'wallet_id' => $record->id, 'tenant_id' => $record->tenant_id, 'transaction_type' => 'top_up',
                            'amount' => (float) $data['amount'], 'balance_after' => $record->balance, 'description' => 'Yêu cầu nạp — chờ duyệt', 'status' => 'pending',
                        ]);
                        $this->billingAudit('wallet.request_topup', $record, null, ['amount' => $data['amount']]);
                        Notification::make()->title('Đã tạo yêu cầu nạp (chờ duyệt)')->success()->send();
                    }),
                Action::make('deduct')->label('Trừ tiêu dùng')->iconButton()->icon('heroicon-m-minus-circle')->color('danger')
                    ->schema([TextInput::make('amount')->label('Số tiền (đ)')->numeric()->required()])
                    ->action(fn (PassThroughWallet $record, array $data) => $this->deduct($record, (float) $data['amount'])),
                Action::make('autoTopup')->label('Cấu hình auto top-up')->iconButton()->icon('heroicon-m-cog-6-tooth')->color('gray')
                    ->fillForm(fn (PassThroughWallet $record) => ['auto_topup_enabled' => $record->auto_topup_enabled, 'auto_topup_amount' => $record->auto_topup_amount, 'low_balance_threshold' => $record->low_balance_threshold])
                    ->schema([
                        Toggle::make('auto_topup_enabled')->label('Bật auto top-up'),
                        TextInput::make('auto_topup_amount')->label('Số tiền auto nạp (đ)')->numeric(),
                        TextInput::make('low_balance_threshold')->label('Ngưỡng số dư thấp (đ)')->numeric(),
                    ])
                    ->action(function (PassThroughWallet $record, array $data): void {
                        $record->update($data);
                        $this->billingAudit('wallet.configure_autotopup', $record, null, $data);
                        Notification::make()->title('Đã lưu cấu hình auto top-up')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có ví')
            ->emptyStateIcon('heroicon-o-wallet')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    /** wire:click từ blade — duyệt yêu cầu nạp đang chờ. */
    public function approveTopUp(int $txnId): void
    {
        $txn = PassThroughTransaction::with('wallet')->find($txnId);
        if (! $txn || $txn->status !== 'pending' || ! $txn->wallet) {
            return;
        }
        $this->topUp($txn->wallet, (float) $txn->amount, 'confirmed', 'wallet.approve_topup');
        $txn->update(['status' => 'confirmed', 'balance_after' => $txn->wallet->fresh()->balance, 'description' => 'Đã duyệt nạp']);
        Notification::make()->title('Đã duyệt & cộng số dư')->success()->send();
    }

    private function topUp(PassThroughWallet $record, float $amount, string $status, string $action): void
    {
        $before = ['balance' => $record->balance];
        $record->increment('balance', $amount);
        PassThroughTransaction::create([
            'wallet_id' => $record->id, 'tenant_id' => $record->tenant_id, 'transaction_type' => 'top_up',
            'amount' => $amount, 'balance_after' => $record->balance, 'description' => 'Nạp tiền', 'status' => $status,
        ]);
        $this->billingAudit($action, $record, $before, ['balance' => $record->balance]);
        Notification::make()->title('Đã nạp '.number_format($amount).'đ')->success()->send();
    }

    private function deduct(PassThroughWallet $record, float $amount): void
    {
        $before = ['balance' => $record->balance];
        $record->decrement('balance', $amount);
        PassThroughTransaction::create([
            'wallet_id' => $record->id, 'tenant_id' => $record->tenant_id, 'transaction_type' => 'deduct',
            'amount' => $amount, 'balance_after' => $record->balance, 'description' => 'Trừ tiêu dùng kênh', 'status' => 'confirmed',
        ]);
        $this->billingAudit('wallet.deduct', $record, $before, ['balance' => $record->balance]);
        if ($record->balance <= $record->low_balance_threshold) {
            Notification::make()->title('⚠ Ví '.self::TYPE[$record->wallet_type].' số dư thấp')->warning()->send();
        } else {
            Notification::make()->title('Đã trừ '.number_format($amount).'đ')->success()->send();
        }
    }
}
