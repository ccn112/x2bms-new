<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesSupportAudit;
use App\Models\SupportSlaPolicy;
use App\Models\SupportTeam;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\SupportTicketStatusLog;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * WEB-UX-30-02/03/04 — Ticket Queue & SLA + Detail + Create.
 *
 * Danh sách ticket (title click → chi tiết), SLA remaining, bulk assign, escalate,
 * close, reopen. Tạo ticket dùng RichEditor cho mô tả. Mọi hành động ghi support_audit_logs.
 */
class SupportTicketQueue extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesSupportAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static ?string $navigationLabel = 'Ticket Queue & SLA';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Hàng đợi ticket & SLA';

    protected static ?string $slug = 'support/tickets';

    protected string $view = 'filament.pages.support-ticket-queue';

    public const STATUS = [
        'new' => ['Mới', 'info'], 'open' => ['Đang mở', 'info'], 'in_progress' => ['Đang xử lý', 'warning'],
        'waiting_customer' => ['Chờ KH', 'warning'], 'escalated' => ['Escalated', 'danger'],
        'resolved' => ['Đã xử lý', 'success'], 'closed' => ['Đóng', 'gray'], 'reopened' => ['Mở lại', 'warning'],
    ];

    public const PRIORITY = ['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'];

    public const SLA = [
        'within_sla' => ['Trong hạn', 'success'], 'near_breach' => ['Sắp trễ', 'warning'],
        'breached' => ['Đã trễ', 'danger'], 'paused_waiting_customer' => ['Tạm dừng', 'gray'], 'resolved' => ['Xong', 'success'],
    ];

    protected function getViewData(): array
    {
        return [
            'kpis' => [
                ['label' => 'Tổng ticket', 'value' => SupportTicket::count(), 'accent' => 'blue'],
                ['label' => 'Đang mở', 'value' => SupportTicket::whereNotIn('status', ['closed', 'resolved'])->count(), 'accent' => 'blue'],
                ['label' => 'Escalated', 'value' => SupportTicket::where('status', 'escalated')->count(), 'accent' => 'red'],
                ['label' => 'Sắp trễ SLA', 'value' => SupportTicket::where('sla_state', 'near_breach')->count(), 'accent' => 'amber'],
                ['label' => 'Đã trễ SLA', 'value' => SupportTicket::where('sla_state', 'breached')->count(), 'accent' => 'red'],
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Tạo ticket')->icon('heroicon-m-plus')->color('primary')->modalWidth('2xl')
                ->schema([
                    Select::make('tenant_id')->label('Tenant')->options(Tenant::orderBy('name')->pluck('name', 'id'))->searchable(),
                    TextInput::make('subject')->label('Tiêu đề')->required()->maxLength(255)->columnSpanFull(),
                    RichEditor::make('description')->label('Mô tả')->columnSpanFull(),
                    Select::make('module')->label('Module')->options(['Reports' => 'Reports', 'API Gateway' => 'API Gateway', 'Webhook' => 'Webhook', 'Data Fix' => 'Data Fix', 'Billing' => 'Billing', 'General' => 'General'])->default('General'),
                    Select::make('priority')->label('Ưu tiên')->options(self::PRIORITY)->default('medium')->required(),
                    Select::make('environment')->label('Môi trường')->options(['production' => 'Production', 'staging' => 'Staging', 'sandbox' => 'Sandbox'])->default('production'),
                    Select::make('team_id')->label('Phân cho đội')->options(SupportTeam::pluck('name', 'id')),
                    TextInput::make('requester_name')->label('Người yêu cầu'),
                ])
                ->action(fn (array $data) => $this->createTicket($data)),
        ];
    }

    private function createTicket(array $data): void
    {
        $sla = SupportSlaPolicy::where('priority', $data['priority'])->first();
        $t = SupportTicket::create($data + [
            'ticket_no' => 'TKT-'.now()->format('Y').'-'.strtoupper(Str::random(5)),
            'status' => 'new', 'sla_state' => 'within_sla', 'sla_policy_id' => $sla?->id,
            'sla_due_at' => $sla ? now()->addMinutes($sla->resolution_minutes) : now()->addDay(),
            'owner_id' => auth()->id(),
        ]);
        SupportTicketStatusLog::create(['support_ticket_id' => $t->id, 'to_status' => 'new', 'changed_by' => auth()->id(), 'created_at' => now()]);
        $this->supportAudit('ticket.created', $t, after: ['ticket_no' => $t->ticket_no]);
        Notification::make()->title('Đã tạo ticket '.$t->ticket_no.' — SLA đã bắt đầu')->success()->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SupportTicket::query()->with(['tenant', 'owner', 'team']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('ticket_no')->label('Mã ticket')->searchable()->weight('medium')->color('primary')
                    ->fontFamily('mono')->size('xs')->action($this->detailAction()),
                TextColumn::make('subject')->label('Tiêu đề')->wrap()->limit(48)->searchable()->action($this->detailAction()),
                TextColumn::make('tenant.name')->label('Tenant')->toggleable(),
                TextColumn::make('module')->label('Module')->badge()->color('gray')->toggleable(),
                TextColumn::make('priority')->label('Ưu tiên')->badge()
                    ->formatStateUsing(fn (string $state) => self::PRIORITY[$state] ?? $state)
                    ->color(fn (string $state) => ['critical' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'success'][$state] ?? 'gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('sla_state')->label('SLA')->badge()
                    ->formatStateUsing(fn (string $state) => self::SLA[$state][0] ?? $state)->color(fn (string $state) => self::SLA[$state][1] ?? 'gray'),
                TextColumn::make('owner.name')->label('Owner')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('priority')->label('Ưu tiên')->options(self::PRIORITY),
                SelectFilter::make('sla_state')->label('SLA')->options(collect(self::SLA)->map(fn ($v) => $v[0])->all()),
            ])
            ->recordActions([
                $this->detailAction(),
                Action::make('assign')->label('Phân công')->iconButton()->icon('heroicon-m-user-plus')->color('info')
                    ->schema([Select::make('team_id')->label('Đội')->options(SupportTeam::pluck('name', 'id'))->required()])
                    ->action(function (array $data, SupportTicket $r): void {
                        $r->update(['team_id' => $data['team_id']]);
                        $this->supportAudit('ticket.assigned', $r, after: ['team_id' => $data['team_id']]);
                        Notification::make()->title('Đã phân công')->success()->send();
                    }),
                Action::make('escalate')->label('Escalate')->iconButton()->icon('heroicon-m-arrow-trending-up')->color('warning')
                    ->visible(fn (SupportTicket $r) => ! in_array($r->status, ['closed', 'resolved'], true))
                    ->schema([Select::make('to_level')->label('Cấp')->options(['L2' => 'L2', 'L3' => 'L3', 'account' => 'Account Manager'])->default('L2')->required(), TextInput::make('reason')->label('Lý do')->required()])
                    ->action(function (array $data, SupportTicket $r): void {
                        $r->escalations()->create(['from_level' => 'L1', 'to_level' => $data['to_level'], 'reason' => $data['reason'], 'status' => 'active', 'escalated_by' => auth()->id()]);
                        $r->update(['status' => 'escalated']);
                        $this->supportAudit('ticket.escalated', $r, after: $data);
                        Notification::make()->title('Đã escalate lên '.$data['to_level'])->success()->send();
                    }),
                Action::make('close')->label('Đóng')->iconButton()->icon('heroicon-m-check-circle')->color('success')
                    ->visible(fn (SupportTicket $r) => ! in_array($r->status, ['closed'], true))
                    ->schema([RichEditor::make('resolution_summary')->label('Tóm tắt xử lý')->required(), TextInput::make('csat_score')->label('CSAT (1-5)')->numeric()])
                    ->action(function (array $data, SupportTicket $r): void {
                        $r->update(['status' => 'closed', 'sla_state' => 'resolved', 'closed_at' => now(), 'resolved_at' => $r->resolved_at ?? now(), 'resolution_summary' => $data['resolution_summary'], 'csat_score' => $data['csat_score'] ?? null]);
                        $this->supportAudit('ticket.closed', $r, reason: 'resolved');
                        Notification::make()->title('Đã đóng ticket')->success()->send();
                    }),
                Action::make('reopen')->label('Mở lại')->iconButton()->icon('heroicon-m-arrow-uturn-left')->color('gray')
                    ->visible(fn (SupportTicket $r) => in_array($r->status, ['closed', 'resolved'], true))->requiresConfirmation()
                    ->action(function (SupportTicket $r): void {
                        $r->update(['status' => 'reopened', 'closed_at' => null, 'reopen_count' => $r->reopen_count + 1]);
                        $this->supportAudit('ticket.reopened', $r);
                        Notification::make()->title('Đã mở lại ticket')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('bulkAssign')->label('Phân công hàng loạt')->icon('heroicon-m-user-group')
                    ->schema([Select::make('team_id')->label('Đội')->options(SupportTeam::pluck('name', 'id'))->required()])
                    ->action(function (array $data, Collection $records): void {
                        foreach ($records as $r) {
                            $r->update(['team_id' => $data['team_id']]);
                            $this->supportAudit('ticket.assigned', $r, after: ['team_id' => $data['team_id'], 'bulk' => true]);
                        }
                        Notification::make()->title('Đã phân công '.$records->count().' ticket')->success()->send();
                    })->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('Chưa có ticket')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    public function detailAction(): Action
    {
        return Action::make('detail')->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (SupportTicket $r) => $r->ticket_no.' — '.$r->subject)
            ->modalContent(fn (SupportTicket $r) => view('filament.pages.support-ticket-detail', [
                'record' => $r->load(['tenant', 'owner', 'team', 'messages.author', 'statusLogs', 'escalations', 'dataCorrectionRequests']),
            ]))
            ->modalWidth('3xl')->modalSubmitAction(false)->modalCancelActionLabel('Đóng')
            ->extraModalFooterActions([
                Action::make('reply')->label('Thêm phản hồi')->color('primary')
                    ->schema([Select::make('type')->label('Loại')->options(['customer' => 'Khách hàng', 'internal' => 'Nội bộ', 'system' => 'Hệ thống'])->default('internal'), RichEditor::make('body')->label('Nội dung')->required()])
                    ->action(function (array $data, SupportTicket $r): void {
                        SupportTicketMessage::create(['support_ticket_id' => $r->id, 'author_id' => auth()->id(), 'author_name' => auth()->user()?->name, 'type' => $data['type'], 'body' => $data['body']]);
                        $this->supportAudit('ticket.message_added', $r, after: ['type' => $data['type']]);
                        Notification::make()->title('Đã thêm phản hồi')->success()->send();
                    }),
            ]);
    }
}
