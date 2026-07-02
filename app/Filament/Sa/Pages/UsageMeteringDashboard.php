<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesBillingAudit;
use App\Models\QuotaAlert;
use App\Models\UsagePeriod;
use App\Models\UsageRecord;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * WEB-UX-27-05 — Theo dõi usage & metering.
 *
 * Ingestion + period-lock workflow: collect/import → recalculate → lock → generate quota alerts.
 * Kỳ đã khóa mới được đưa vào sinh hóa đơn. Unlock cần audit.
 */
class UsageMeteringDashboard extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesBillingAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static string|\UnitEnum|null $navigationGroup = 'SaaS Billing';

    protected static ?string $navigationLabel = 'Usage & Metering';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Theo dõi usage & metering';

    protected static ?string $slug = 'billing/usage';

    protected string $view = 'filament.pages.usage-metering-dashboard';

    private function latestPeriod(): ?UsagePeriod
    {
        return UsagePeriod::latest('period_start')->first();
    }

    protected function getViewData(): array
    {
        $period = $this->latestPeriod();
        $overageTotal = $period ? (float) UsageRecord::where('usage_period_id', $period->id)->sum('overage_amount') : 0;

        return [
            'period' => $period,
            'kpis' => [
                ['label' => 'Kỳ hiện tại', 'value' => $period?->code ?? '—', 'accent' => 'blue'],
                ['label' => 'Trạng thái kỳ', 'value' => ['open' => 'Mở', 'calculating' => 'Đang tính', 'locked' => 'Đã khóa'][$period?->status] ?? '—', 'accent' => $period?->status === 'locked' ? 'green' : 'amber'],
                ['label' => 'Bản ghi usage', 'value' => $period ? UsageRecord::where('usage_period_id', $period->id)->count() : 0, 'accent' => 'blue'],
                ['label' => 'Tổng overage', 'value' => number_format($overageTotal / 1_000_000, 1).'tr', 'accent' => 'red'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        $period = $this->latestPeriod();

        return $table
            ->query(UsageRecord::query()->with('tenant')->where('usage_period_id', $period?->id ?? 0))
            ->defaultSort('overage_amount', 'desc')
            ->columns([
                TextColumn::make('tenant.name')->label('Công ty')->searchable()->weight('medium'),
                TextColumn::make('meter_type')->label('Meter')->badge()->color('gray'),
                TextColumn::make('usage_value')->label('Sử dụng')->numeric()->sortable(),
                TextColumn::make('included_limit')->label('Hạn mức')->numeric()->toggleable(),
                TextColumn::make('overage_value')->label('Vượt')->numeric()
                    ->color(fn (UsageRecord $r) => $r->overage_value > 0 ? 'danger' : 'gray'),
                TextColumn::make('overage_amount')->label('Phí vượt')->money('VND')
                    ->color(fn (UsageRecord $r) => $r->overage_amount > 0 ? 'danger' : 'gray'),
                TextColumn::make('source')->label('Nguồn')->badge()->color('gray')->toggleable(),
                TextColumn::make('status')->label('TT')->badge()
                    ->color(fn (string $state) => $state === 'locked' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('meter_type')->label('Meter')
                    ->options(fn () => UsageRecord::where('usage_period_id', $this->latestPeriod()?->id ?? 0)->distinct()->pluck('meter_type', 'meter_type')->all()),
            ])
            ->headerActions([
                Action::make('recalculate')->label('Tính lại')->icon('heroicon-m-calculator')->color('gray')
                    ->visible(fn () => $period && $period->status !== 'locked')->requiresConfirmation()
                    ->action(fn () => $this->recalculate()),
                Action::make('lock')->label('Khóa kỳ')->icon('heroicon-m-lock-closed')->color('warning')
                    ->visible(fn () => $period && $period->status !== 'locked')->requiresConfirmation()
                    ->modalDescription('Khóa kỳ để đưa usage vào sinh hóa đơn.')
                    ->action(fn () => $this->lockPeriod(true)),
                Action::make('unlock')->label('Mở khóa kỳ')->icon('heroicon-m-lock-open')->color('danger')
                    ->visible(fn () => $period && $period->status === 'locked')->requiresConfirmation()
                    ->modalDescription('Mở khóa cần quyền + ghi audit.')
                    ->action(fn () => $this->lockPeriod(false)),
                Action::make('generateAlerts')->label('Sinh cảnh báo vượt hạn')->icon('heroicon-m-bell-alert')->color('primary')
                    ->visible(fn () => $period && $period->status === 'locked')
                    ->action(fn () => $this->generateAlerts()),
            ])
            ->emptyStateHeading('Chưa có dữ liệu usage')
            ->emptyStateIcon('heroicon-o-signal')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    private function recalculate(): void
    {
        $period = $this->latestPeriod();
        if (! $period) {
            return;
        }
        foreach (UsageRecord::where('usage_period_id', $period->id)->get() as $r) {
            $over = max(0, (float) $r->usage_value - (float) $r->included_limit);
            $r->update(['overage_value' => $over, 'status' => 'calculated']);
        }
        $period->update(['status' => 'calculating']);
        $this->billingAudit('usage.recalculate', $period, null, ['status' => 'calculating']);
        Notification::make()->title('Đã tính lại overage')->success()->send();
    }

    private function lockPeriod(bool $lock): void
    {
        $period = $this->latestPeriod();
        if (! $period) {
            return;
        }
        $before = ['status' => $period->status];
        if ($lock) {
            $period->update(['status' => 'locked', 'locked_at' => now(), 'locked_by' => auth()->user()->name]);
            UsageRecord::where('usage_period_id', $period->id)->update(['status' => 'locked']);
        } else {
            $period->update(['status' => 'open', 'locked_at' => null, 'locked_by' => null]);
            UsageRecord::where('usage_period_id', $period->id)->update(['status' => 'calculated']);
        }
        $this->billingAudit($lock ? 'usage.lock' : 'usage.unlock', $period, $before, ['status' => $period->status]);
        Notification::make()->title($lock ? 'Đã khóa kỳ' : 'Đã mở khóa kỳ')->success()->send();
    }

    private function generateAlerts(): void
    {
        $period = $this->latestPeriod();
        if (! $period) {
            return;
        }
        $created = 0;
        foreach (UsageRecord::where('usage_period_id', $period->id)->where('overage_value', '>', 0)->get() as $r) {
            $pct = $r->included_limit > 0 ? round(($r->overage_value / $r->included_limit) * 100, 2) : 100;
            $alert = QuotaAlert::firstOrCreate(
                ['tenant_id' => $r->tenant_id, 'usage_period_id' => $period->id, 'meter_type' => $r->meter_type],
                [
                    'code' => 'QA-'.$period->id.'-'.$r->tenant_id.'-'.$r->meter_type,
                    'usage_value' => $r->usage_value, 'included_limit' => $r->included_limit, 'over_percent' => $pct,
                    'estimated_fee' => $r->overage_amount, 'recommendation' => 'Mua add-on hoặc nâng gói', 'status' => 'open',
                ]
            );
            if ($alert->wasRecentlyCreated) {
                $created++;
            }
        }
        $this->billingAudit('usage.generate_alerts', $period, null, ['created' => $created]);
        Notification::make()->title("Đã sinh {$created} cảnh báo vượt hạn")->success()->send();
    }
}
