<?php

namespace App\Filament\Pages;

use App\Models\FeeType;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/**
 * BQL-03-01 — Biểu phí & quy tắc tính phí (Fee catalogue).
 * Catalogue of fee rules with KPI counters, filters and a "notable rules"
 * gallery. Reads fee_types (tenant-scoped) enriched with the BQL-03 display
 * columns. No fee cycle here — that is BQL-03-02.
 */
class FeeCatalog extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Tài chính – Phí';

    protected static ?string $navigationLabel = 'Khoản thu';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Biểu phí & quy tắc tính phí';

    protected static ?string $slug = 'fees/catalog';

    protected string $view = 'filament.pages.fee-catalog';

    /** Category code → Vietnamese label shown in the "Nhóm phí" column. */
    public const CATEGORY_LABELS = [
        'management' => 'Phí quản lý',
        'parking' => 'Phí gửi xe',
        'utility' => 'Điện nước',
        'reserve' => 'Quỹ & dự phòng',
        'service' => 'Phí dịch vụ',
        'surcharge' => 'Phụ thu',
        'other' => 'Khác',
    ];

    public const FREQUENCY_LABELS = [
        'monthly' => 'Hàng tháng',
        'quarterly' => 'Hàng quý',
        'yearly' => 'Hàng năm',
        'per_use' => 'Theo lần',
    ];

    protected function tenantId(): ?int
    {
        return app(CurrentContext::class)->tenantId();
    }

    protected function getViewData(): array
    {
        $tid = $this->tenantId();
        $base = fn () => FeeType::query()->when($tid, fn ($q) => $q->where('tenant_id', $tid));

        $rows = (clone $base())
            ->with(['formulas'])
            ->orderBy('code')
            ->get()
            ->map(function (FeeType $f) {
                $status = $f->status ?: 'active';

                return [
                    'code' => $f->code,
                    'name' => $f->name,
                    'category' => self::CATEGORY_LABELS[$f->category] ?? ucfirst((string) $f->category),
                    'applies_to' => $f->applies_to ?: '—',
                    'formula' => $f->formula_text ?: ($f->formulas->first()->expression ?? '—'),
                    'frequency' => self::FREQUENCY_LABELS[$f->frequency] ?? 'Hàng tháng',
                    'vat' => $f->vat_percent > 0 ? rtrim(rtrim(number_format((float) $f->vat_percent, 2), '0'), '.').'%' : 'Không VAT',
                    'effective' => $f->effective_from?->format('d/m/Y') ?? '—',
                    'status' => $status,
                    'status_label' => match ($status) {
                        'active' => 'Đang áp dụng',
                        'pending' => 'Sắp hiệu lực',
                        'inactive' => 'Tạm ngưng',
                        default => $status,
                    },
                    'status_tone' => match ($status) {
                        'active' => 'green',
                        'pending' => 'amber',
                        'inactive' => 'red',
                        default => 'slate',
                    },
                    'is_complex' => (bool) $f->is_complex,
                ];
            })
            ->all();

        $count = fn (string $col, $val) => (clone $base())->where($col, $val)->count();

        return [
            'kpis' => [
                ['label' => 'Biểu phí đang áp dụng', 'value' => $count('status', 'active'), 'accent' => 'blue'],
                ['label' => 'Sắp hiệu lực', 'value' => $count('status', 'pending'), 'accent' => 'amber'],
                ['label' => 'Tạm ngưng', 'value' => $count('status', 'inactive'), 'accent' => 'red'],
                ['label' => 'Công thức phức tạp', 'value' => $count('is_complex', true), 'accent' => 'blue'],
                ['label' => 'Cập nhật tháng này', 'value' => (clone $base())->where('updated_at', '>=', now()->startOfMonth())->count(), 'accent' => 'green'],
            ],
            'rows' => $rows,
            'notable' => array_slice(array_filter($rows, fn ($r) => $r['status'] === 'active'), 0, 5),
        ];
    }
}
