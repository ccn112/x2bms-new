<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesBillingAudit;
use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use App\Models\BillingReconciliation;
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
 * WEB-UX-27-08 — Chi tiết hóa đơn & trạng thái thanh toán.
 *
 * Duyệt/gửi/hủy hóa đơn, ghi nhận thanh toán (partial→partially_paid, đủ→paid), đối soát.
 * Không hard delete tài chính. Mọi hành động ghi billing_audit_logs.
 */
class InvoiceManagement extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesBillingAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'SaaS Billing';

    protected static ?string $navigationLabel = 'Hóa đơn & thanh toán';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Hóa đơn & trạng thái thanh toán';

    protected static ?string $slug = 'billing/invoices';

    protected string $view = 'filament.pages.invoice-management';

    public const STATUS = [
        'draft' => ['Nháp', 'gray'], 'pending_approval' => ['Chờ duyệt', 'warning'], 'issued' => ['Đã phát hành', 'info'],
        'sent' => ['Đã gửi', 'info'], 'partially_paid' => ['Trả một phần', 'warning'], 'paid' => ['Đã thanh toán', 'success'],
        'overdue' => ['Quá hạn', 'danger'], 'voided' => ['Đã hủy', 'gray'], 'credited' => ['Đã ghi có', 'gray'],
    ];

    protected function getViewData(): array
    {
        return [
            'kpis' => [
                ['label' => 'Chờ duyệt', 'value' => BillingInvoice::where('status', 'pending_approval')->count(), 'accent' => 'amber'],
                ['label' => 'Đã phát hành', 'value' => BillingInvoice::whereIn('status', ['issued', 'sent'])->count(), 'accent' => 'blue'],
                ['label' => 'Quá hạn', 'value' => BillingInvoice::where('status', 'overdue')->orWhere(fn ($q) => $q->whereIn('status', ['issued', 'sent', 'partially_paid'])->whereDate('due_date', '<', now()))->count(), 'accent' => 'red'],
                ['label' => 'Công nợ còn lại', 'value' => number_format((float) BillingInvoice::whereNotIn('status', ['paid', 'voided'])->sum('remaining_amount') / 1_000_000, 1).'tr', 'accent' => 'red'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(BillingInvoice::query()->with('tenant'))
            ->defaultSort('issue_date', 'desc')
            ->columns([
                TextColumn::make('invoice_no')->label('Số HĐ')->searchable()->color('primary')->weight('medium'),
                TextColumn::make('tenant.name')->label('Công ty')->searchable(),
                TextColumn::make('period')->label('Kỳ')->badge()->color('gray'),
                TextColumn::make('total_amount')->label('Tổng')->money('VND')->sortable(),
                TextColumn::make('paid_amount')->label('Đã trả')->money('VND')->color('success')->toggleable(),
                TextColumn::make('remaining_amount')->label('Còn lại')->money('VND')
                    ->color(fn (BillingInvoice $record) => $record->remaining_amount > 0 ? 'danger' : 'gray'),
                TextColumn::make('due_date')->label('Hạn')->date('d/m/Y')
                    ->color(fn (BillingInvoice $record) => $record->due_date && $record->due_date->isPast() && $record->remaining_amount > 0 ? 'danger' : 'gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
            ])
            ->recordActions([
                $this->viewAction(),
                Action::make('approve')->label('Duyệt')->iconButton()->icon('heroicon-m-check-circle')->color('success')
                    ->visible(fn (BillingInvoice $record) => in_array($record->status, ['draft', 'pending_approval'], true))
                    ->requiresConfirmation()
                    ->action(function (BillingInvoice $record): void {
                        $before = ['status' => $record->status];
                        $record->update(['status' => 'issued', 'issue_date' => $record->issue_date ?? now()]);
                        $this->billingAudit('invoice.approve', $record, $before, ['status' => 'issued']);
                        Notification::make()->title('Đã duyệt & phát hành')->success()->send();
                    }),
                Action::make('send')->label('Gửi')->iconButton()->icon('heroicon-m-paper-airplane')->color('info')
                    ->visible(fn (BillingInvoice $record) => $record->status === 'issued')->requiresConfirmation()
                    ->action(function (BillingInvoice $record): void {
                        $record->update(['status' => 'sent']);
                        $this->billingAudit('invoice.send', $record, null, ['status' => 'sent']);
                        Notification::make()->title('Đã gửi hóa đơn')->success()->send();
                    }),
                Action::make('recordPayment')->label('Ghi nhận thanh toán')->iconButton()->icon('heroicon-m-banknotes')->color('success')
                    ->visible(fn (BillingInvoice $record) => ! in_array($record->status, ['paid', 'voided'], true))
                    ->schema([
                        TextInput::make('amount')->label('Số tiền (đ)')->numeric()->required(),
                        Select::make('payment_method')->label('Hình thức')->options(['bank_transfer' => 'Chuyển khoản', 'cash' => 'Tiền mặt', 'card' => 'Thẻ'])->default('bank_transfer'),
                        TextInput::make('transaction_ref')->label('Mã giao dịch'),
                    ])
                    ->action(fn (BillingInvoice $record, array $data) => $this->recordPayment($record, $data)),
                Action::make('reconcile')->label('Đối soát')->iconButton()->icon('heroicon-m-scale')->color('gray')
                    ->visible(fn (BillingInvoice $record) => $record->payments()->exists())
                    ->requiresConfirmation()->modalDescription('Đánh dấu đã đối soát khớp thanh toán ↔ hóa đơn.')
                    ->action(fn (BillingInvoice $record) => $this->reconcile($record)),
                Action::make('void')->label('Hủy')->iconButton()->icon('heroicon-m-x-circle')->color('danger')
                    ->visible(fn (BillingInvoice $record) => ! in_array($record->status, ['paid', 'voided'], true))
                    ->schema([Textarea::make('reason')->label('Lý do hủy')->required()->rows(2)])
                    ->action(function (BillingInvoice $record, array $data): void {
                        $before = ['status' => $record->status];
                        $record->update(['status' => 'voided']);
                        $this->billingAudit('invoice.void', $record, $before, ['status' => 'voided'], $data['reason']);
                        Notification::make()->title('Đã hủy hóa đơn')->warning()->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có hóa đơn')
            ->emptyStateIcon('heroicon-o-document-currency-dollar')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (BillingInvoice $record) => $record->invoice_no.' — '.($record->tenant?->name ?? ''))
            ->modalContent(fn (BillingInvoice $record) => view('filament.pages.invoice-detail', [
                'record' => $record->load(['tenant', 'lines', 'payments']),
                'statusMap' => self::STATUS,
            ]))
            ->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    private function recordPayment(BillingInvoice $record, array $data): void
    {
        $amount = (float) $data['amount'];
        BillingPayment::create([
            'invoice_id' => $record->id, 'tenant_id' => $record->tenant_id, 'payment_method' => $data['payment_method'] ?? 'bank_transfer',
            'amount' => $amount, 'paid_at' => now(), 'transaction_ref' => $data['transaction_ref'] ?? null, 'status' => 'confirmed',
        ]);
        $before = ['paid' => $record->paid_amount, 'status' => $record->status];
        $paid = (float) $record->paid_amount + $amount;
        $remaining = max(0, (float) $record->total_amount - $paid);
        $status = $remaining <= 0 ? 'paid' : 'partially_paid';
        $record->update(['paid_amount' => $paid, 'remaining_amount' => $remaining, 'status' => $status]);
        $this->billingAudit('invoice.payment', $record, $before, ['paid' => $paid, 'status' => $status]);
        Notification::make()->title($status === 'paid' ? 'Đã thanh toán đủ' : 'Đã ghi nhận thanh toán một phần')->success()->send();
    }

    private function reconcile(BillingInvoice $record): void
    {
        $payment = $record->payments()->latest('paid_at')->first();
        $diff = (float) $record->total_amount - (float) $record->paid_amount;
        BillingReconciliation::create([
            'tenant_id' => $record->tenant_id, 'invoice_id' => $record->id, 'payment_id' => $payment?->id,
            'bank_transaction_ref' => $payment?->transaction_ref, 'status' => abs($diff) < 1 ? 'matched' : 'mismatch',
            'difference_amount' => $diff, 'confirmed_by' => auth()->id(), 'confirmed_at' => now(),
        ]);
        $this->billingAudit('invoice.reconcile', $record, null, ['difference' => $diff, 'status' => abs($diff) < 1 ? 'matched' : 'mismatch']);
        Notification::make()->title(abs($diff) < 1 ? 'Đối soát khớp' : 'Đối soát lệch — cần kiểm tra')->success()->send();
    }
}
