<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\PlanChangeRequest;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/**
 * HQ-02-10 — Yêu cầu nâng cấp / hạ gói / gia hạn.
 * KPI + tab loại yêu cầu + bảng + search. Số khớp ảnh (128 · 18 · 27 · 78).
 */
class PlanChangeRequests extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-up-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & Gói dịch vụ';

    protected static ?string $navigationLabel = 'Nâng/hạ gói & gia hạn';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Yêu cầu nâng cấp / hạ gói / gia hạn';

    protected static ?string $slug = 'billing/plan-changes';

    protected string $view = 'filament.hq.pages.plan-change-requests';

    public string $type = 'all';

    public string $search = '';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $base = PlanChangeRequest::where('tenant_id', $tid);
        $all = (clone $base)->with('project')->latest('created_at')->get();

        $rows = $all->filter(function ($r) {
            if ($this->type !== 'all' && $r->change_type !== $this->type) {
                return false;
            }
            if ($this->search !== '') {
                $q = mb_strtolower($this->search);

                return str_contains(mb_strtolower($r->request_no), $q)
                    || str_contains(mb_strtolower((string) $r->project?->name), $q)
                    || str_contains(mb_strtolower((string) $r->content), $q);
            }

            return true;
        })->take(40)->map(fn ($r) => [
            'no' => $r->request_no, 'project' => $r->project?->name ?? '—',
            'type' => $r->change_type, 'content' => $r->content,
            'date' => optional($r->created_at)->format('d/m/Y H:i'), 'status' => $r->status,
        ])->values();

        return [
            'kpi' => [
                'total' => $all->count(),
                'processing' => $all->where('status', 'processing')->count(),
                'pending' => $all->where('status', 'pending_approval')->count(),
                'completed' => $all->where('status', 'completed')->count(),
            ],
            'tabs' => [
                'all' => ['Tất cả', $all->count()],
                'upgrade' => ['Nâng cấp', $all->where('change_type', 'upgrade')->count()],
                'downgrade' => ['Hạ gói', $all->where('change_type', 'downgrade')->count()],
                'renew' => ['Gia hạn', $all->where('change_type', 'renew')->count()],
            ],
            'rows' => $rows,
        ];
    }
}
