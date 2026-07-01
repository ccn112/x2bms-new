<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesBillingAudit;
use App\Models\BillingInvoice;
use App\Models\BillingInvoiceLine;
use App\Models\SubscriptionAddon;
use App\Models\TenantSubscription;
use App\Models\UsagePeriod;
use App\Models\UsageRecord;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

/**
 * WEB-UX-27-07 — Sinh hóa đơn SaaS.
 *
 * Gộp thuê bao + add-on + overage (từ kỳ usage đã KHÓA) + pass-through thành hóa đơn nháp,
 * xem trước + nhật ký lỗi. Hóa đơn nháp → duyệt/phát hành ở màn Hóa đơn & thanh toán.
 */
class InvoiceGeneration extends Page
{
    use PlatformScreen;
    use WritesBillingAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-plus';

    protected static string|\UnitEnum|null $navigationGroup = 'SaaS Billing';

    protected static ?string $navigationLabel = 'Sinh hóa đơn';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Sinh hóa đơn SaaS';

    protected static ?string $slug = 'billing/invoice-generation';

    protected string $view = 'filament.pages.invoice-generation';

    protected function getViewData(): array
    {
        $period = UsagePeriod::latest('period_start')->first();
        $eligible = TenantSubscription::with(['tenant', 'plan'])
            ->whereIn('status', ['active', 'pending_renewal', 'past_due'])->get();
        $periodKey = $period?->period_end?->format('Y-m');

        return [
            'period' => $period,
            'periodLocked' => $period && $period->status === 'locked',
            'eligible' => $eligible->map(function (TenantSubscription $s) use ($period, $periodKey) {
                $overage = $period ? (float) UsageRecord::where('usage_period_id', $period->id)
                    ->where('tenant_id', $s->tenant_id)->sum('overage_amount') : 0;
                $addon = (float) SubscriptionAddon::where('subscription_id', $s->id)->where('status', 'active')->sum('mrr');
                $exists = BillingInvoice::where('subscription_id', $s->id)->where('period', $periodKey)->exists();

                return [
                    'sub' => $s, 'base' => (float) $s->mrr, 'addon' => $addon, 'overage' => $overage,
                    'total' => (float) $s->mrr + $addon + $overage, 'exists' => $exists,
                ];
            }),
            'periodKey' => $periodKey,
            'recentDrafts' => BillingInvoice::with('tenant')->where('status', 'draft')->latest('created_at')->limit(8)->get(),
        ];
    }

    public function generateAction(): Action
    {
        return Action::make('generate')
            ->label('Sinh hóa đơn nháp cho kỳ')
            ->icon('heroicon-m-bolt')->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Sinh hóa đơn hàng loạt')
            ->modalDescription('Tạo hóa đơn NHÁP cho các thuê bao đủ điều kiện từ kỳ usage đã khóa. Bỏ qua thuê bao đã có hóa đơn kỳ này.')
            ->action(fn () => $this->generate());
    }

    public function generate(): void
    {
        $period = UsagePeriod::latest('period_start')->first();
        if (! $period || $period->status !== 'locked') {
            Notification::make()->title('Kỳ usage chưa khóa — không thể sinh hóa đơn')->danger()->send();

            return;
        }
        $periodKey = $period->period_end?->format('Y-m');
        $created = 0;
        $skipped = 0;

        foreach (TenantSubscription::whereIn('status', ['active', 'pending_renewal', 'past_due'])->get() as $s) {
            if (BillingInvoice::where('subscription_id', $s->id)->where('period', $periodKey)->exists()) {
                $skipped++;

                continue;
            }
            DB::transaction(function () use ($s, $period, $periodKey, &$created): void {
                $base = (float) $s->mrr;
                $addons = SubscriptionAddon::where('subscription_id', $s->id)->where('status', 'active')->get();
                $overage = (float) UsageRecord::where('usage_period_id', $period->id)->where('tenant_id', $s->tenant_id)->sum('overage_amount');
                $subtotal = $base + (float) $addons->sum('mrr') + $overage;
                $tax = round($subtotal * 0.1);
                $total = $subtotal + $tax;

                $inv = BillingInvoice::create([
                    'invoice_no' => 'INV-'.$periodKey.'-'.$s->tenant_id.str_pad((string) $s->id, 3, '0', STR_PAD_LEFT),
                    'tenant_id' => $s->tenant_id, 'subscription_id' => $s->id, 'period' => $periodKey, 'status' => 'draft',
                    'issue_date' => now(), 'due_date' => now()->addDays(15), 'subtotal' => $subtotal,
                    'discount_total' => 0, 'tax_total' => $tax, 'total_amount' => $total, 'paid_amount' => 0,
                    'remaining_amount' => $total, 'currency' => 'VND',
                ]);
                BillingInvoiceLine::create(['invoice_id' => $inv->id, 'line_type' => 'subscription', 'description' => 'Thuê bao '.($s->plan?->name ?? ''), 'quantity' => 1, 'unit_price' => $base, 'amount' => $base]);
                foreach ($addons as $a) {
                    BillingInvoiceLine::create(['invoice_id' => $inv->id, 'line_type' => 'addon', 'description' => 'Add-on: '.$a->name, 'quantity' => 1, 'unit_price' => $a->mrr, 'amount' => $a->mrr]);
                }
                if ($overage > 0) {
                    BillingInvoiceLine::create(['invoice_id' => $inv->id, 'line_type' => 'usage_overage', 'description' => 'Overage kỳ '.$period->code, 'quantity' => 1, 'unit_price' => $overage, 'amount' => $overage]);
                }
                BillingInvoiceLine::create(['invoice_id' => $inv->id, 'line_type' => 'tax', 'description' => 'VAT 10%', 'quantity' => 1, 'unit_price' => $tax, 'amount' => $tax, 'tax_rate' => 10]);

                $this->billingAudit('invoice.generate', $inv, null, ['total' => $total, 'period' => $periodKey]);
                $created++;
            });
        }

        Notification::make()->title("Đã sinh {$created} hóa đơn nháp (bỏ qua {$skipped} đã có)")->success()->send();
    }
}
