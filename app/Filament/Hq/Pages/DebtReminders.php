<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\DebtReminderCampaign;
use App\Models\MetricSnapshot;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-05-08 — Nhắc nợ & chiến dịch thu hồi. */
class DebtReminders extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|\UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Nhắc nợ & thu hồi';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Lịch sử nhắc nợ & chiến dịch thu hồi';

    protected static ?string $slug = 'debt-reminders';

    protected string $view = 'filament.hq.pages.debt-reminders';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $kpi = MetricSnapshot::where('tenant_id', $tid)->where('metric_key', 'reminder_kpi')->get()
            ->mapWithKeys(fn ($m) => [$m->dimension['metric'] => (float) $m->value]);
        $rows = DebtReminderCampaign::where('tenant_id', $tid)->with('owner')->latest('started_at')->get()
            ->map(fn ($c) => [
                'name' => $c->name, 'scope' => $c->scope, 'channel' => $c->channel, 'status' => $c->status,
                'target' => $c->target_count, 'sent' => $c->sent_count, 'response' => (float) $c->response_rate,
                'committed' => (float) $c->committed_amount, 'collected' => (float) $c->collected_amount,
                'owner' => $c->owner?->name ?? '—',
            ]);

        return ['kpi' => $kpi, 'rows' => $rows];
    }
}
