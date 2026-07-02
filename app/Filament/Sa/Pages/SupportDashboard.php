<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Models\DataCorrectionRequest;
use App\Models\SupportEscalation;
use App\Models\SupportReport;
use App\Models\SupportTicket;
use BackedEnum;
use Filament\Pages\Page;

/**
 * WEB-UX-30-01 — Support Dashboard.
 *
 * Tổng quan ticket, SLA, escalation, data correction, CSAT. Số % (SLA/CSAT) đọc từ
 * support_reports snapshot; các đếm (tổng, ưu tiên, escalation) tính live từ DB.
 */
class SupportDashboard extends Page
{
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static ?string $navigationLabel = 'Support Dashboard';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Support Dashboard';

    protected static ?string $slug = 'support/dashboard';

    protected string $view = 'filament.pages.support-dashboard';

    protected function getViewData(): array
    {
        $snapshot = (array) (SupportReport::where('code', 'DASH-CURRENT')->value('metrics_json') ?? []);
        $byPriority = SupportTicket::selectRaw('priority, count(*) c')->groupBy('priority')->pluck('c', 'priority');
        $open = SupportTicket::whereNotIn('status', ['closed', 'resolved'])->count();

        return [
            'kpis' => [
                ['label' => 'Ticket đang mở', 'value' => $open, 'accent' => 'blue'],
                ['label' => 'SLA compliance', 'value' => ($snapshot['sla_compliance'] ?? 0).'%', 'accent' => 'green'],
                ['label' => 'Tỉ lệ breach', 'value' => ($snapshot['breach_rate'] ?? 0).'%', 'accent' => 'red'],
                ['label' => 'Escalation đang mở', 'value' => $snapshot['open_escalations'] ?? SupportEscalation::where('status', 'active')->count(), 'accent' => 'amber'],
                ['label' => 'Near breach', 'value' => $snapshot['near_breach'] ?? 0, 'accent' => 'amber'],
                ['label' => 'CSAT', 'value' => ($snapshot['csat'] ?? 0).'/5', 'accent' => 'green'],
            ],
            'priority' => [
                ['label' => 'Critical', 'value' => $byPriority['critical'] ?? 0, 'accent' => 'red'],
                ['label' => 'High', 'value' => $byPriority['high'] ?? 0, 'accent' => 'amber'],
                ['label' => 'Medium', 'value' => $byPriority['medium'] ?? 0, 'accent' => 'blue'],
                ['label' => 'Low', 'value' => $byPriority['low'] ?? 0, 'accent' => 'green'],
            ],
            'byStatus' => SupportTicket::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
            'statusTotal' => max(1, SupportTicket::count()),
            'escalatedTickets' => SupportTicket::with('tenant')->where('status', 'escalated')->latest('updated_at')->limit(6)->get(),
            'openCorrections' => DataCorrectionRequest::whereIn('status', ['pending_approval', 'approved', 'executing'])->count(),
            'recentTickets' => SupportTicket::with(['tenant', 'owner'])->latest('created_at')->limit(6)->get(),
        ];
    }
}
