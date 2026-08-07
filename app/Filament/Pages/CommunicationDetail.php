<?php

namespace App\Filament\Pages;

use App\Enums\CommunicationWorkflowStatus as WS;
use App\Filament\Concerns\WritesAudit;
use App\Models\Notification as NotificationModel;
use App\Models\NotificationRecipient;
use App\Services\Notifications\NotificationApprovalService;
use App\Services\Notifications\NotificationPublisher;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * BQL-NOTI-07 (chi tiết + hành động vòng đời) + BQL-NOTI-08 (bảng người nhận, PII mask).
 * Đọc record qua ?record= (tương thích panel discoverPages; pretty-route là follow-up).
 * Actions đi qua service domain (duyệt/từ chối/yêu cầu sửa/phát hành/hủy). Snapshot đã gửi
 * KHÔNG sửa. Không có trong menu (mở từ Trung tâm thông báo).
 */
class CommunicationDetail extends Page implements HasTable
{
    use InteractsWithTable;
    use WritesAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $slug = 'notifications/detail';

    protected string $view = 'filament.pages.communication-detail';

    public ?int $record = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->record = (int) request('record');
        abort_unless($this->record > 0, 404);
        abort_unless($this->campaign()->canManageBy(auth()->user()), 403);
    }

    public function getTitle(): string
    {
        return 'Chi tiết truyền thông';
    }

    private function campaign(): NotificationModel
    {
        return NotificationModel::query()
            ->with(['channels', 'audiences', 'latestSnapshot', 'approvals.steps', 'creator'])
            ->findOrFail($this->record);
    }

    protected function getViewData(): array
    {
        $n = $this->campaign();
        $wf = $n->workflow_status instanceof WS ? $n->workflow_status : WS::from((string) $n->workflow_status);
        $delivered = $n->deliveryLogs()->whereIn('status', ['sent', 'delivered', 'read'])->count();
        $failed = $n->deliveryLogs()->whereIn('status', ['failed', 'bounced'])->count();

        return [
            'n' => $n,
            'wf' => $wf,
            'snapshot' => $n->latestSnapshot,
            'approval' => $n->approvals->sortByDesc('id')->first(),
            'kpis' => [
                ['label' => 'Người nhận', 'value' => number_format($n->recipient_count), 'accent' => 'blue', 'icon' => 'heroicon-o-users'],
                ['label' => 'Đã gửi/nhận', 'value' => number_format($delivered), 'accent' => 'green', 'icon' => 'heroicon-o-paper-airplane'],
                ['label' => 'Đã đọc', 'value' => number_format($n->read_count), 'accent' => 'teal', 'icon' => 'heroicon-o-eye'],
                ['label' => 'Lỗi', 'value' => number_format($failed), 'accent' => $failed ? 'red' : 'gray', 'icon' => 'heroicon-o-exclamation-triangle'],
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        $n = $this->campaign();
        $wf = $n->workflow_status instanceof WS ? $n->workflow_status : WS::from((string) $n->workflow_status);
        $approval = $n->approvals->sortByDesc('id')->first();
        $canApprove = $approval && $approval->status->value === 'requested' && $approval->requested_by_id !== auth()->id();

        return [
            Action::make('approve')->label('Duyệt')->icon('heroicon-m-check')->color('success')
                ->visible($wf === WS::PendingApproval && $canApprove)->requiresConfirmation()
                ->action(fn () => $this->act('approved')),
            Action::make('request_changes')->label('Yêu cầu sửa')->icon('heroicon-m-pencil')->color('warning')
                ->visible($wf === WS::PendingApproval && $canApprove)
                ->schema([Textarea::make('reason')->label('Lý do')->required()])
                ->action(fn (array $data) => $this->act('changes_requested', $data['reason'] ?? null)),
            Action::make('reject')->label('Từ chối')->icon('heroicon-m-x-mark')->color('danger')
                ->visible($wf === WS::PendingApproval && $canApprove)
                ->schema([Textarea::make('reason')->label('Lý do')->required()])
                ->action(fn (array $data) => $this->act('rejected', $data['reason'] ?? null)),
            Action::make('publish')->label('Phát hành')->icon('heroicon-m-paper-airplane')->color('primary')
                ->visible(in_array($wf, [WS::Approved, WS::Scheduled], true))->requiresConfirmation()
                ->modalDescription('Chốt snapshot và gửi tới người nhận. Không thể sửa nội dung sau khi gửi.')
                ->action(fn () => $this->publish()),
            Action::make('cancel')->label('Hủy chiến dịch')->icon('heroicon-m-no-symbol')->color('gray')
                ->visible(! $wf->isTerminal() && ! $wf->isDispatched())->requiresConfirmation()
                ->action(fn () => $this->cancel()),
            Action::make('clone')->label('Nhân bản')->icon('heroicon-m-document-duplicate')->color('gray')
                ->action(fn () => $this->clone()),
        ];
    }

    private function act(string $decision, ?string $reason = null): void
    {
        $n = $this->campaign();
        $approval = $n->approvals()->latest('id')->first();
        if (! $approval) {
            Notification::make()->title('Không có tuyến duyệt đang mở')->danger()->send();

            return;
        }
        try {
            app(NotificationApprovalService::class)->act($approval, auth()->id(), $decision, $reason);
            $this->audit('notification.'.$decision, 'Duyệt ('.$decision.'): '.$n->title, NotificationModel::class, $n->id);
            Notification::make()->title('Đã cập nhật duyệt')->success()->send();
        } catch (\DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    private function publish(): void
    {
        $n = $this->campaign();
        try {
            app(NotificationPublisher::class)->publish($n, auth()->id());
            $this->audit('notification.publish', 'Phát hành: '.$n->title, NotificationModel::class, $n->id);
            Notification::make()->title('Đã phát hành')->body(number_format($n->recipient_count).' người nhận')->success()->send();
        } catch (\DomainException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    private function cancel(): void
    {
        $n = $this->campaign();
        app(\App\Services\Notifications\CampaignStateMachine::class)->transition($n, WS::Cancelled, ['actor_id' => auth()->id()]);
        $this->audit('notification.cancel', 'Hủy: '.$n->title, NotificationModel::class, $n->id);
        Notification::make()->title('Đã hủy chiến dịch')->success()->send();
    }

    private function clone(): void
    {
        $n = $this->campaign();
        $copy = $n->replicate(['workflow_status', 'status', 'published_at', 'sent_at', 'completed_at', 'recipient_count', 'read_count', 'snapshot_version', 'audience_snapshot_hash']);
        $copy->fill([
            'code' => 'NTF-'.strtoupper(Str::random(6)),
            'title' => $n->title.' (bản sao)',
            'workflow_status' => WS::Draft->value, 'status' => 'draft',
            'recipient_count' => 0, 'read_count' => 0, 'snapshot_version' => 0,
            'published_at' => null, 'sent_at' => null, 'completed_at' => null, 'audience_snapshot_hash' => null,
            'created_by_id' => auth()->id(),
        ]);
        $copy->save();
        $this->audit('notification.clone', 'Nhân bản: '.$n->title, NotificationModel::class, $copy->id);
        Notification::make()->title('Đã nhân bản (nháp)')->success()->send();
        $this->redirect(static::getUrl().'?record='.$copy->id);
    }

    // ---- BQL-NOTI-08 recipients table (PII mask) ------------------------

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => NotificationRecipient::query()->where('notification_id', $this->record)->with(['resident', 'apartment']))
            ->columns([
                TextColumn::make('resident.full_name')->label('Cư dân')->searchable()->wrap(),
                TextColumn::make('apartment.code')->label('Căn hộ')->badge()->color('gray'),
                TextColumn::make('role')->label('Vai trò')->badge()
                    ->formatStateUsing(fn (?string $s) => ['owner' => 'Chủ hộ', 'tenant' => 'Người thuê', 'member' => 'Thành viên'][$s] ?? $s),
                TextColumn::make('contact')->label('Liên hệ (ẩn)')
                    ->getStateUsing(fn (NotificationRecipient $r) => $this->maskContact($r)),
                TextColumn::make('channels_planned')->label('Kênh')
                    ->getStateUsing(fn (NotificationRecipient $r) => collect($r->channels_planned ?? [])->implode(', ')),
                TextColumn::make('delivery')->label('Trạng thái gửi')->badge()
                    ->getStateUsing(fn (NotificationRecipient $r) => $this->deliveryLabel($r))
                    ->color(fn (string $state) => match ($state) {
                        'Đã đọc' => 'success', 'Đã gửi' => 'info', 'Lỗi' => 'danger', default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('role')->label('Vai trò')->options(['owner' => 'Chủ hộ', 'tenant' => 'Người thuê', 'member' => 'Thành viên']),
                SelectFilter::make('status')->label('Trạng thái resolve')->options(['resolved' => 'Đã resolve', 'suppressed' => 'Bị chặn']),
            ])
            ->emptyStateHeading('Chưa có người nhận (resolve khi gửi duyệt)')
            ->emptyStateIcon('heroicon-o-users')
            ->paginated([25, 50, 100]);
    }

    /** Ẩn PII: chỉ hiện đuôi email/số điện thoại (spec 10 §Privacy). */
    private function maskContact(NotificationRecipient $r): string
    {
        $res = $r->resident;
        if (! $res) {
            return '—';
        }
        $email = $res->email ? Str::mask($res->email, '*', 1, max(1, strpos($res->email, '@') - 2)) : null;
        $phone = $res->phone ? Str::mask($res->phone, '*', 3, max(0, strlen($res->phone) - 5)) : null;

        return collect([$phone, $email])->filter()->implode(' · ') ?: '—';
    }

    private function deliveryLabel(NotificationRecipient $r): string
    {
        $log = \App\Models\NotificationDeliveryLog::where('notification_id', $r->notification_id)
            ->where('user_id', $r->user_id)->orderByDesc('id')->first();
        if (! $log) {
            return 'Chưa gửi';
        }

        return match ($log->status) {
            'read' => 'Đã đọc', 'sent', 'delivered' => 'Đã gửi', 'failed', 'bounced' => 'Lỗi', default => 'Chờ',
        };
    }
}
