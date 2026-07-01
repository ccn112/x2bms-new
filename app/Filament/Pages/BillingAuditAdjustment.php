<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesBillingAudit;
use App\Models\BillingAdjustment;
use App\Models\BillingAuditLog;
use App\Models\CreditNote;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * WEB-UX-27-10 — Audit billing & điều chỉnh hóa đơn.
 *
 * Case điều chỉnh: approve/reject/request-more-info → issue credit note (áp vào hóa đơn).
 * Timeline audit đầy đủ. Không hard delete. Ghi billing_audit_logs.
 */
class BillingAuditAdjustment extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesBillingAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'SaaS Billing';

    protected static ?string $navigationLabel = 'Audit & điều chỉnh';

    protected static ?int $navigationSort = 9;

    protected static ?string $title = 'Audit billing & điều chỉnh hóa đơn';

    protected static ?string $slug = 'billing/adjustments';

    protected string $view = 'filament.pages.billing-audit-adjustment';

    public const STATUS = [
        'pending_approval' => ['Chờ duyệt', 'warning'], 'need_more_info' => ['Cần bổ sung', 'info'],
        'approved' => ['Đã duyệt', 'success'], 'rejected' => ['Từ chối', 'danger'],
    ];

    public const TYPE = [
        'overcharge_sms' => 'Thu thừa SMS', 'duplicate_overage' => 'Overage trùng', 'tax_correction' => 'Sửa thuế',
        'usage_adjustment' => 'Điều chỉnh usage', 'courtesy_discount' => 'Chiết khấu thiện chí', 'credit_note_issued' => 'Đã ghi có',
    ];

    protected function getViewData(): array
    {
        return [
            'kpis' => [
                ['label' => 'Chờ duyệt', 'value' => BillingAdjustment::where('status', 'pending_approval')->count(), 'accent' => 'amber'],
                ['label' => 'Đã duyệt', 'value' => BillingAdjustment::where('status', 'approved')->count(), 'accent' => 'green'],
                ['label' => 'Credit note', 'value' => CreditNote::count(), 'accent' => 'blue'],
                ['label' => 'Giá trị điều chỉnh', 'value' => number_format((float) BillingAdjustment::where('status', 'approved')->sum('amount') / 1_000_000, 1).'tr', 'accent' => 'blue'],
            ],
            'auditTimeline' => BillingAuditLog::with('actor')->latest('created_at')->limit(12)->get(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(BillingAdjustment::query()->with(['tenant', 'invoice', 'requester']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('case_id')->label('Case')->searchable()->color('primary')->weight('medium'),
                TextColumn::make('tenant.name')->label('Công ty')->searchable(),
                TextColumn::make('invoice.invoice_no')->label('Hóa đơn')->placeholder('—')->toggleable(),
                TextColumn::make('adjustment_type')->label('Loại')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => self::TYPE[$state] ?? $state),
                TextColumn::make('amount')->label('Số tiền')->money('VND')
                    ->color(fn (BillingAdjustment $record) => $record->amount < 0 ? 'danger' : 'success'),
                TextColumn::make('requester.name')->label('Người tạo')->placeholder('—')->toggleable(),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all())->default('pending_approval'),
                SelectFilter::make('adjustment_type')->label('Loại')->options(self::TYPE),
            ])
            ->recordActions([
                Action::make('approve')->label('Duyệt')->iconButton()->icon('heroicon-m-check-circle')->color('success')
                    ->visible(fn (BillingAdjustment $record) => in_array($record->status, ['pending_approval', 'need_more_info'], true))
                    ->requiresConfirmation()
                    ->action(function (BillingAdjustment $record): void {
                        $before = ['status' => $record->status];
                        $record->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
                        $this->billingAudit('adjustment.approve', $record, $before, ['status' => 'approved']);
                        Notification::make()->title('Đã duyệt điều chỉnh')->success()->send();
                    }),
                Action::make('reject')->label('Từ chối')->iconButton()->icon('heroicon-m-x-circle')->color('danger')
                    ->visible(fn (BillingAdjustment $record) => in_array($record->status, ['pending_approval', 'need_more_info'], true))
                    ->schema([Textarea::make('reason')->label('Lý do từ chối')->required()->rows(2)])
                    ->action(function (BillingAdjustment $record, array $data): void {
                        $before = ['status' => $record->status];
                        $record->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'approved_at' => now()]);
                        $this->billingAudit('adjustment.reject', $record, $before, ['status' => 'rejected'], $data['reason']);
                        Notification::make()->title('Đã từ chối')->warning()->send();
                    }),
                Action::make('needMore')->label('Yêu cầu bổ sung')->iconButton()->icon('heroicon-m-information-circle')->color('info')
                    ->visible(fn (BillingAdjustment $record) => $record->status === 'pending_approval')
                    ->schema([Textarea::make('reason')->label('Nội dung cần bổ sung')->required()->rows(2)])
                    ->action(function (BillingAdjustment $record, array $data): void {
                        $record->update(['status' => 'need_more_info']);
                        $this->billingAudit('adjustment.need_more', $record, null, ['status' => 'need_more_info'], $data['reason']);
                        Notification::make()->title('Đã yêu cầu bổ sung')->success()->send();
                    }),
                Action::make('creditNote')->label('Phát hành credit note')->iconButton()->icon('heroicon-m-receipt-refund')->color('success')
                    ->visible(fn (BillingAdjustment $record) => $record->status === 'approved' && ! CreditNote::where('adjustment_id', $record->id)->exists())
                    ->requiresConfirmation()->modalDescription('Tạo credit note & áp vào hóa đơn (giảm công nợ).')
                    ->action(fn (BillingAdjustment $record) => $this->issueCreditNote($record)),
            ])
            ->emptyStateHeading('Không có case điều chỉnh')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    private function issueCreditNote(BillingAdjustment $record): void
    {
        $cn = CreditNote::create([
            'credit_note_no' => 'CN-'.$record->case_id, 'tenant_id' => $record->tenant_id, 'invoice_id' => $record->invoice_id,
            'adjustment_id' => $record->id, 'amount' => abs((float) $record->amount), 'reason' => $record->reason,
            'status' => 'issued', 'issued_at' => now(),
        ]);
        // Áp vào hóa đơn: giảm remaining (nếu có hóa đơn).
        if ($record->invoice) {
            $inv = $record->invoice;
            $newRemaining = max(0, (float) $inv->remaining_amount - abs((float) $record->amount));
            $inv->update(['remaining_amount' => $newRemaining, 'status' => $newRemaining <= 0 ? 'credited' : $inv->status]);
            $cn->update(['status' => 'applied', 'applied_at' => now()]);
        }
        $record->update(['adjustment_type' => $record->adjustment_type, 'status' => 'approved']);
        $this->billingAudit('adjustment.credit_note', $record, null, ['credit_note' => $cn->credit_note_no, 'amount' => $cn->amount]);
        Notification::make()->title('Đã phát hành credit note '.$cn->credit_note_no)->success()->send();
    }
}
