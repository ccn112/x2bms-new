<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\BillingInvoice;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-02-07 — Hóa đơn platform (danh sách hóa đơn nền tảng của công ty). */
class PlatformInvoices extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null $navigationGroup = 'Billing & Gói dịch vụ';
    protected static ?string $navigationLabel = 'Hóa đơn platform';
    protected static ?int $navigationSort = 7;
    protected static ?string $title = 'Hóa đơn platform';
    protected static ?string $slug = 'billing/invoices';
    protected string $view = 'filament.hq.pages.platform-invoices';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $inv = BillingInvoice::where('tenant_id', $tid)->orderByDesc('period')->get();

        return [
            'rows' => $inv->map(fn ($i) => [
                'no' => $i->invoice_no, 'period' => $i->period, 'status' => $i->status,
                'issue' => optional($i->issue_date)->format('d/m/Y'), 'due' => optional($i->due_date)->format('d/m/Y'),
                'total' => (float) $i->total_amount, 'paid' => (float) $i->paid_amount, 'remaining' => (float) $i->remaining_amount,
            ]),
            'kpi' => [
                'total' => (float) $inv->sum('total_amount'),
                'paid' => (float) $inv->sum('paid_amount'),
                'outstanding' => (float) $inv->sum('remaining_amount'),
                'count' => $inv->count(),
            ],
        ];
    }
}
