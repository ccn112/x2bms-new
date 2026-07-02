<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\FinanceScope;
use App\Models\Statement;
use BackedEnum;
use Filament\Pages\Page;

/**
 * BQL-03-09 — Chi tiết bảng kê căn hộ (Statement detail).
 * Apartment/resident info, the fee line breakdown with VAT, payment overview,
 * publish/reminder timeline and an action panel. Reached from the statement list.
 */
class StatementDetail extends Page
{
    use FinanceScope;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'statements/{record}';

    protected string $view = 'filament.pages.statement-detail';

    public Statement $record;

    public function mount(Statement $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return 'Chi tiết bảng kê căn hộ';
    }

    protected function getViewData(): array
    {
        $s = $this->record->load(['apartment.residents', 'apartment.building', 'billingPeriod', 'lines']);
        $resident = $s->apartment?->residents->firstWhere('pivot.is_primary', true)?->full_name
            ?? $s->apartment?->residents->first()?->full_name ?? '—';

        $lines = $s->lines->values()->map(fn ($l, $i) => [
            'no' => $i + 1,
            'name' => $l->fee_type,
            'qty' => $l->quantity ? rtrim(rtrim(number_format((float) $l->quantity, 2, ',', '.'), '0'), ',') : '1',
            'unit_price' => self::money((float) ($l->unit_price ?? $l->amount)),
            'amount' => self::money((float) $l->amount),
        ])->all();

        $total = (float) $s->total_amount;
        $preVat = (int) round($total / 1.08);
        $vat = (int) round($total - $preVat);
        $paid = (float) $s->paid_amount;
        $remaining = $total - $paid;

        $published = $s->approval_status === 'published';
        $timeline = [];
        if ($s->published_at) {
            $timeline[] = ['icon' => 'doc', 'title' => 'Phát hành bảng kê', 'desc' => 'Bảng kê '.($s->billingPeriod->label ?? '').' được phát hành.', 'by' => $s->assignee_name ?? 'Hệ thống', 'at' => $s->published_at->format('d/m/Y H:i')];
        }
        if ($s->viewed_at) {
            $timeline[] = ['icon' => 'eye', 'title' => 'Cư dân đã xem', 'desc' => 'Cư dân mở bảng kê trên ứng dụng.', 'by' => $resident, 'at' => $s->viewed_at->format('d/m/Y H:i')];
        }
        if ($paid > 0) {
            $timeline[] = ['icon' => 'check', 'title' => 'Ghi nhận thanh toán', 'desc' => 'Thanh toán '.self::money($paid).' đ.', 'by' => $resident, 'at' => ($s->published_at?->copy()->addDays(6)->format('d/m/Y H:i')) ?? '—'];
        }
        if ($s->due_date) {
            $timeline[] = ['icon' => 'clock', 'title' => 'Hạn thanh toán', 'desc' => 'Hạn thanh toán của bảng kê.', 'by' => 'Hệ thống', 'at' => $s->due_date->format('d/m/Y')];
        }

        $statusMeta = $remaining <= 0 ? ['Đã thanh toán', 'green'] : ($published ? ['Đã phát hành', 'blue'] : ['Chờ duyệt', 'amber']);

        return [
            's' => $s,
            'apt' => $s->apartment,
            'resident' => $resident,
            'lines' => $lines,
            'preVat' => self::money($preVat),
            'vat' => self::money($vat),
            'total' => self::money($total),
            'paid' => self::money($paid),
            'remaining' => self::money($remaining),
            'remainingRaw' => $remaining,
            'period' => $s->billingPeriod,
            'published' => $published,
            'due' => $s->due_date?->format('d/m/Y') ?? '—',
            'timeline' => $timeline,
            'statusLabel' => $statusMeta[0],
            'statusTone' => $statusMeta[1],
        ];
    }
}
