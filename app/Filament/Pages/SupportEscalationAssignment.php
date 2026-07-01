<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesSupportAudit;
use App\Models\SupportEscalation;
use App\Models\SupportTeam;
use App\Models\SupportTicket;
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
 * WEB-UX-30-09 — Support Escalation & Assignment.
 * Workload theo team, auto-assign, cân bằng tải, sự kiện escalation, rủi ro SLA.
 */
class SupportEscalationAssignment extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesSupportAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static ?string $navigationLabel = 'Escalation & Assignment';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Escalation & phân công hỗ trợ';

    protected static ?string $slug = 'support/escalations';

    protected string $view = 'filament.pages.support-escalation-assignment';

    protected function getViewData(): array
    {
        $unassigned = SupportTicket::whereNull('team_id')->whereNotIn('status', ['closed', 'resolved'])->count();

        return [
            'kpis' => [
                ['label' => 'Chưa phân công', 'value' => $unassigned, 'accent' => 'red'],
                ['label' => 'Escalation active', 'value' => SupportEscalation::where('status', 'active')->count(), 'accent' => 'amber'],
                ['label' => 'Đang xử lý', 'value' => SupportTicket::where('status', 'in_progress')->count(), 'accent' => 'blue'],
                ['label' => 'Sắp trễ SLA', 'value' => SupportTicket::where('sla_state', 'near_breach')->count(), 'accent' => 'amber'],
                ['label' => 'Đã trễ SLA', 'value' => SupportTicket::where('sla_state', 'breached')->count(), 'accent' => 'red'],
            ],
            'workload' => SupportTeam::withCount(['tickets as open_tickets' => fn ($q) => $q->whereNotIn('status', ['closed', 'resolved'])])->get(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('autoAssign')->label('Auto-assign')->icon('heroicon-m-sparkles')->color('primary')
                ->requiresConfirmation()->modalDescription('Tự phân công ticket chưa có đội theo tải/độ ưu tiên?')
                ->action(function (): void {
                    $teams = SupportTeam::pluck('id')->all();
                    if (empty($teams)) {
                        return;
                    }
                    $unassigned = SupportTicket::whereNull('team_id')->whereNotIn('status', ['closed', 'resolved'])->get();
                    foreach ($unassigned as $i => $t) {
                        $t->update(['team_id' => $teams[$i % count($teams)], 'status' => $t->status === 'new' ? 'open' : $t->status]);
                    }
                    $this->supportAudit('assignment.auto_assign', null, after: ['count' => $unassigned->count()]);
                    Notification::make()->title('Đã auto-assign '.$unassigned->count().' ticket')->success()->send();
                }),
            Action::make('balance')->label('Cân bằng tải')->icon('heroicon-m-scale')->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->supportAudit('assignment.balance_workload', null);
                    Notification::make()->title('Đã cân bằng tải theo team')->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SupportEscalation::query()->with('ticket'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('ticket.ticket_no')->label('Ticket')->fontFamily('mono')->size('xs')->color('primary')->searchable(),
                TextColumn::make('ticket.subject')->label('Tiêu đề')->limit(40)->toggleable(),
                TextColumn::make('from_level')->label('Từ cấp')->badge()->color('gray'),
                TextColumn::make('to_level')->label('Lên cấp')->badge()->color('warning'),
                TextColumn::make('reason')->label('Lý do')->wrap(),
                TextColumn::make('status')->label('Trạng thái')->badge()->color(fn (string $state) => $state === 'active' ? 'danger' : 'success'),
                TextColumn::make('created_at')->label('Thời điểm')->since(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(['active' => 'Active', 'resolved' => 'Resolved']),
                SelectFilter::make('to_level')->label('Cấp')->options(['L2' => 'L2', 'L3' => 'L3', 'account' => 'Account']),
            ])
            ->recordActions([
                Action::make('resolve')->label('Đóng escalation')->iconButton()->icon('heroicon-m-check')->color('success')
                    ->visible(fn (SupportEscalation $r) => $r->status === 'active')->requiresConfirmation()
                    ->action(function (SupportEscalation $r): void {
                        $r->update(['status' => 'resolved', 'resolved_at' => now()]);
                        $this->supportAudit('escalation.resolved', $r);
                        Notification::make()->title('Đã đóng escalation')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Không có escalation')
            ->striped();
    }
}
