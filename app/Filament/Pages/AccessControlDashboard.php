<?php

namespace App\Filament\Pages;

use App\Models\AccessCard;
use App\Models\AccessDevice;
use App\Models\AccessLog;
use App\Models\ResidentApprovalRequest;
use App\Models\Vehicle;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * BQL-02 — Dashboard An ninh & Kiểm soát ra vào (Access Control overview).
 * Landing for the access module: KPIs across approvals / cards / vehicles / access
 * events / devices, recent access log and expiring cards — all scoped + live.
 * (The BQL-02-10 image is a per-resident access profile; this landing follows the text spec.)
 */
class AccessControlDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'An ninh & Kiểm soát';

    protected static ?string $navigationLabel = 'Tổng quan kiểm soát';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Tổng quan an ninh & kiểm soát ra vào';

    protected static ?string $slug = 'access';

    protected string $view = 'filament.pages.access-control-dashboard';

    /** @return array<int> */
    private function buildingIds(): array
    {
        return app(CurrentContext::class)->buildingIds() ?: [0];
    }

    protected function getViewData(): array
    {
        $bids = $this->buildingIds();
        $soon = now()->addDays(30);

        $cards = fn () => AccessCard::query()->whereIn('building_id', $bids);
        $vehicles = fn () => Vehicle::query()->whereIn('building_id', $bids);
        $logs = fn () => AccessLog::query()->whereIn('building_id', $bids);

        $pendingApprovals = ResidentApprovalRequest::whereIn('building_id', $bids)->where('status', 'pending')->count()
            + (clone $vehicles())->whereIn('status', ['pending', 'reviewing', 'need_more'])->count();

        $recent = (clone $logs())->latest('event_at')->limit(10)->get()->map(fn (AccessLog $l) => [
            'time' => $l->event_at ? Carbon::parse($l->event_at)->format('d/m H:i') : '—',
            'gate' => $l->gate ?: $l->device_name ?: '—',
            'direction' => $l->direction === 'out' ? ['Ra', 'red'] : ['Vào', 'green'],
            'method' => $l->method ?: '—',
            'status' => $l->status ?: 'ok',
        ])->all();

        $expiring = (clone $cards())->whereNotNull('valid_to')->whereBetween('valid_to', [now(), $soon])
            ->with('resident')->orderBy('valid_to')->limit(8)->get()->map(fn (AccessCard $c) => [
                'card_no' => $c->card_no,
                'resident' => $c->resident?->full_name ?? '—',
                'valid_to' => $c->valid_to?->format('d/m/Y'),
                'days' => $c->valid_to ? (int) now()->diffInDays($c->valid_to, false) : null,
            ])->all();

        return [
            'kpis' => [
                ['label' => 'Sự kiện ra/vào hôm nay', 'value' => (clone $logs())->whereDate('event_at', today())->count(), 'accent' => 'blue'],
                ['label' => 'Thẻ đang hoạt động', 'value' => (clone $cards())->where('status', 'active')->count(), 'accent' => 'green'],
                ['label' => 'Thẻ sắp hết hạn', 'value' => (clone $cards())->whereNotNull('valid_to')->whereBetween('valid_to', [now(), $soon])->count(), 'accent' => 'amber'],
                ['label' => 'Xe đang hoạt động', 'value' => (clone $vehicles())->where('status', 'active')->count(), 'accent' => 'teal'],
                ['label' => 'Yêu cầu chờ duyệt', 'value' => $pendingApprovals, 'accent' => 'amber'],
                ['label' => 'Thiết bị online', 'value' => AccessDevice::whereIn('building_id', $bids)->whereIn('status', ['online', 'active'])->count(), 'accent' => 'green'],
            ],
            'recent' => $recent,
            'expiring' => $expiring,
        ];
    }
}
