<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\EmployeeAssignmentHistory;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/**
 * HQ-01-07 — Lịch sử luân chuyển nhân sự. KPI + bảng lịch sử điều chuyển + chip trạng thái duyệt.
 */
class AssignmentHistory extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Nhân sự & BQL';

    protected static ?string $navigationLabel = 'Lịch sử luân chuyển';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Lịch sử luân chuyển nhân sự';

    protected static ?string $slug = 'assignment-histories';

    protected string $view = 'filament.hq.pages.assignment-history';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $items = EmployeeAssignmentHistory::where('tenant_id', $tid)
            ->with(['employee.user', 'fromProject', 'toProject'])
            ->latest('effective_at')->get();

        return [
            'rows' => $items->map(fn ($h) => [
                'code' => $h->transfer_code,
                'name' => $h->employee?->user?->name ?? '—',
                'from' => $h->fromProject?->name ?? '—',
                'to' => $h->toProject?->name ?? '—',
                'reason' => $h->reason,
                'at' => optional($h->effective_at)->format('d/m/Y'),
                'status' => $h->status,
            ]),
            'kpi' => [
                'total' => $items->count(),
                'effective' => $items->where('status', 'effective')->count(),
                'pending' => $items->where('status', 'pending_approval')->count(),
                'approved' => $items->where('status', 'approved')->count(),
            ],
        ];
    }
}
