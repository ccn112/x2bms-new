<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Models\IntegrationConnection;
use App\Models\IntegrationCredential;
use App\Models\IntegrationEvent;
use App\Models\IntegrationIncident;
use App\Models\IntegrationRetryJob;
use App\Models\WebhookEndpoint;
use BackedEnum;
use Filament\Pages\Page;

/**
 * WEB-UX-28-01 — Integration Overview Dashboard.
 *
 * Giám sát tổng thể: kết nối, API requests, webhook success rate, sự cố, retry
 * queue, SLA. Chỉ SuperAdmin (platform). Mọi số liệu tính từ DB.
 */
class IntegrationOverviewDashboard extends Page
{
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static ?string $navigationLabel = 'Tổng quan tích hợp';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Trung tâm tích hợp — Tổng quan';

    protected static ?string $slug = 'integrations/overview';

    protected string $view = 'filament.pages.integration-overview-dashboard';

    protected function getViewData(): array
    {
        $total = IntegrationConnection::count();
        $active = IntegrationConnection::where('status', 'active')->count();
        $warning = IntegrationConnection::where('status', 'warning')->count();
        $incident = IntegrationConnection::where('status', 'incident')->count();
        $avgSuccess = (float) IntegrationConnection::whereNotNull('success_rate_24h')->avg('success_rate_24h');
        $events24h = IntegrationEvent::where('created_at', '>=', now()->subDay())->count();

        $connectionsByCategory = IntegrationConnection::with('category')->orderBy('name')->get()
            ->groupBy(fn ($c) => $c->category?->name ?? 'Khác');

        $eventStatus = IntegrationEvent::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        $webhookAvg = (float) WebhookEndpoint::whereNotNull('success_rate')->avg('success_rate');

        return [
            'kpis' => [
                ['label' => 'Tổng kết nối', 'value' => $total, 'accent' => 'blue'],
                ['label' => 'Đang hoạt động', 'value' => $active, 'accent' => 'green'],
                ['label' => 'Cảnh báo', 'value' => $warning, 'accent' => 'amber'],
                ['label' => 'Sự cố', 'value' => $incident, 'accent' => 'red'],
                ['label' => 'Success rate (24h)', 'value' => number_format($avgSuccess, 1).'%', 'accent' => 'green'],
                ['label' => 'Sự kiện 24h', 'value' => number_format($events24h), 'accent' => 'blue'],
            ],
            'connectionsByCategory' => $connectionsByCategory,
            'eventStatus' => $eventStatus,
            'eventTotal' => max(1, $eventStatus->sum()),
            'webhookAvg' => $webhookAvg,
            'retryByStatus' => IntegrationRetryJob::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
            'openIncidents' => IntegrationIncident::whereIn('status', ['open', 'investigating'])->orderByDesc('started_at')->limit(5)->get(),
            'expiringCreds' => IntegrationCredential::with('connection')->where('status', 'expiring')->limit(5)->get(),
            'recentEvents' => IntegrationEvent::orderByDesc('created_at')->limit(8)->get(),
        ];
    }
}
