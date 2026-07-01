<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ProvidesAiContext;
use App\Filament\Concerns\WritesAudit;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Notification as NotificationModel;
use App\Models\NotificationAudience;
use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\Resident;
use App\Models\Tenant;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * BQL-3 — Trung tâm thông báo (WEB-04 / WEB-UX-08 / QL-NOTI). Soạn → chọn phạm vi
 * 3 lớp (audiences) → lịch/phát hành → theo dõi đã gửi/đã đọc. Danh sách theo
 * quyền xem (Notification::scopeVisibleTo); sửa/phát hành theo canManageBy.
 */
class NotificationCenter extends Page implements HasTable
{
    use InteractsWithTable;
    use ProvidesAiContext;
    use WritesAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|\UnitEnum|null $navigationGroup = 'Vận hành';

    protected static ?string $navigationLabel = 'Thông báo';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Trung tâm thông báo';

    protected static ?string $slug = 'notifications/center';

    protected string $view = 'filament.pages.notification-center';

    public const STATUS = [
        'draft' => ['Nháp', 'gray'], 'scheduled' => ['Hẹn giờ', 'warning'],
        'published' => ['Đã phát hành', 'success'], 'archived' => ['Lưu trữ', 'gray'],
    ];

    public const TYPE = [
        'announcement' => 'Thông báo', 'billing' => 'Phí', 'maintenance' => 'Bảo trì',
        'emergency' => 'Khẩn cấp', 'community' => 'Cộng đồng', 'system' => 'Hệ thống',
    ];

    /** @return \Illuminate\Database\Eloquent\Builder<NotificationModel> */
    private function visible()
    {
        return NotificationModel::query()->visibleTo(auth()->user());
    }

    protected function getViewData(): array
    {
        $published = (clone $this->visible())->where('status', 'published');
        $recipients = (int) (clone $published)->sum('recipient_count');
        $reads = (int) (clone $published)->sum('read_count');

        $this->shareAiContext([
            'title' => 'Truyền thông',
            'lines' => ['Tỉ lệ đọc trung bình: '.($recipients ? round($reads / $recipients * 100, 1) : 0).'%.'],
        ]);

        return [
            'kpis' => [
                ['label' => 'Đã phát hành', 'value' => (clone $published)->count(), 'accent' => 'green'],
                ['label' => 'Hẹn giờ', 'value' => (clone $this->visible())->where('status', 'scheduled')->count(), 'accent' => 'amber'],
                ['label' => 'Nháp', 'value' => (clone $this->visible())->where('status', 'draft')->count(), 'accent' => 'blue'],
                ['label' => 'Tỉ lệ đã đọc', 'value' => ($recipients ? round($reads / $recipients * 100, 1) : 0).'%', 'sub' => number_format($reads).'/'.number_format($recipients), 'accent' => 'teal'],
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        $u = auth()->user();
        $tier = $u->isPlatformAdmin() ? 'Toàn hệ thống' : ($u->isTenantOperator() ? 'Công ty' : 'Dự án');

        return [
            Action::make('compose')
                ->label('Soạn thông báo')->icon('heroicon-m-pencil-square')->color('primary')
                ->modalHeading('Soạn thông báo ('.$tier.')')->modalWidth('2xl')
                ->schema($this->composeSchema())
                ->action(fn (array $data) => $this->createNotification($data)),
        ];
    }

    /** Các scope phạm vi cho phép theo cấp người dùng. */
    private function scopeOptions(): array
    {
        $u = auth()->user();
        if ($u->isPlatformAdmin()) {
            return ['all' => 'Toàn hệ thống', 'tenant' => 'Công ty', 'project' => 'Dự án', 'building' => 'Tòa nhà'];
        }
        if ($u->isTenantOperator()) {
            return ['project' => 'Dự án', 'building' => 'Tòa nhà', 'apartment' => 'Căn hộ'];
        }

        return ['building' => 'Tòa nhà', 'apartment' => 'Căn hộ'];
    }

    /** @return array<int, \Filament\Forms\Components\Component> */
    private function composeSchema(): array
    {
        return [
            Select::make('type')->label('Loại')->options(self::TYPE)->default('announcement')->required(),
            TextInput::make('title')->label('Tiêu đề')->required()->maxLength(255)->columnSpanFull(),
            TextInput::make('summary')->label('Tóm tắt')->maxLength(255)->columnSpanFull(),
            RichEditor::make('body')->label('Nội dung')
                ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link', 'h2', 'h3', 'undo', 'redo'])->columnSpanFull(),
            Select::make('priority')->label('Ưu tiên')->options(['low' => 'Thấp', 'normal' => 'Thường', 'high' => 'Cao', 'urgent' => 'Khẩn'])->default('normal')->required(),
            Select::make('audience_scope')->label('Phạm vi nhận')->options($this->scopeOptions())->required()->live()
                ->default(array_key_first($this->scopeOptions())),
            Select::make('audience_target')->label('Chọn đối tượng')->searchable()
                ->options(fn (Get $get) => match ($get('audience_scope')) {
                    'tenant' => Tenant::orderBy('name')->pluck('name', 'id')->all(),
                    'project' => Project::orderBy('name')->pluck('name', 'id')->all(),
                    'building' => Building::orderBy('name')->pluck('name', 'id')->all(),
                    'apartment' => Apartment::orderBy('code')->pluck('code', 'id')->all(),
                    default => [],
                })
                ->visible(fn (Get $get) => $get('audience_scope') && $get('audience_scope') !== 'all')
                ->required(fn (Get $get) => in_array($get('audience_scope'), ['tenant', 'project', 'building', 'apartment'], true)),
            CheckboxList::make('channels')->label('Kênh gửi')
                ->options(['app' => 'App', 'email' => 'Email', 'sms' => 'SMS', 'zalo' => 'Zalo'])->default(['app'])->columns(4),
            Toggle::make('publish_now')->label('Phát hành ngay')->default(false)->live(),
            DateTimePicker::make('publish_at')->label('Hẹn giờ phát hành')->visible(fn (Get $get) => ! $get('publish_now')),
        ];
    }

    private function creatorOwner(): array
    {
        $u = auth()->user();
        if ($u->isPlatformAdmin()) {
            return ['owner_level' => 'platform', 'tenant_id' => null, 'project_id' => null];
        }
        if ($u->isTenantOperator()) {
            return ['owner_level' => 'tenant', 'tenant_id' => $u->tenant_id, 'project_id' => null];
        }

        return ['owner_level' => 'project', 'tenant_id' => $u->tenant_id, 'project_id' => app(CurrentContext::class)->projectId() ?? $u->project_id];
    }

    private function createNotification(array $data): void
    {
        $now = $data['publish_now'] ?? false;
        $scheduledAt = $data['publish_at'] ?? null;
        $status = $now ? 'published' : ($scheduledAt ? 'scheduled' : 'draft');

        $n = NotificationModel::create($this->creatorOwner() + [
            'code' => 'NTF-'.strtoupper(Str::random(6)),
            'type' => $data['type'], 'title' => $data['title'], 'summary' => $data['summary'] ?? null,
            'body' => $data['body'] ?? null, 'priority' => $data['priority'], 'status' => $status,
            'publish_at' => $scheduledAt, 'published_at' => $now ? now() : null,
            'created_by_id' => auth()->id(), 'published_by_id' => $now ? auth()->id() : null,
        ]);

        NotificationAudience::create([
            'notification_id' => $n->id, 'scope_type' => $data['audience_scope'],
            'scope_id' => $data['audience_scope'] === 'all' ? null : ($data['audience_target'] ?? null),
        ]);
        foreach (($data['channels'] ?? ['app']) as $ch) {
            NotificationChannel::create(['notification_id' => $n->id, 'channel' => $ch]);
        }
        if ($now) {
            $this->applyPublish($n);
        }

        $this->audit('notification.create', 'Soạn thông báo: '.$n->title.' ('.$status.')', NotificationModel::class, $n->id);
        Notification::make()->title($now ? 'Đã phát hành thông báo' : 'Đã lưu thông báo')->success()->send();
    }

    /** Ước tính người nhận theo phạm vi audience. */
    private function applyPublish(NotificationModel $n): void
    {
        $aud = $n->audiences->first();
        $count = 0;
        if ($aud) {
            $q = Resident::query();
            $count = match ($aud->scope_type) {
                'building' => (clone $q)->where('building_id', $aud->scope_id)->count(),
                'apartment' => \App\Models\ResidentApartmentRelation::where('apartment_id', $aud->scope_id)->count(),
                'project' => (clone $q)->whereIn('building_id', Building::where('project_id', $aud->scope_id)->pluck('id'))->count(),
                'tenant' => (clone $q)->where('tenant_id', $aud->scope_id)->count(),
                default => (clone $q)->count(),
            };
        }
        $n->update(['recipient_count' => $count, 'published_at' => $n->published_at ?? now(), 'status' => 'published', 'published_by_id' => auth()->id()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->visible()->with(['audiences', 'channels']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Tiêu đề')->searchable()->wrap()->color('primary')->weight('medium')
                    ->description(fn (NotificationModel $r) => $r->code)
                    ->action($this->viewAction()),
                TextColumn::make('type')->label('Loại')->badge()->color('gray')->formatStateUsing(fn (string $state) => self::TYPE[$state] ?? $state),
                TextColumn::make('owner_level')->label('Cấp')->badge()
                    ->formatStateUsing(fn (string $state) => NotificationModel::OWNER_LEVEL[$state] ?? $state)
                    ->color(fn (string $state) => ['platform' => 'blue', 'tenant' => 'teal', 'project' => 'gray'][$state] ?? 'gray'),
                TextColumn::make('priority')->label('Ưu tiên')->badge()
                    ->color(fn (string $state) => ['urgent' => 'danger', 'high' => 'warning'][$state] ?? 'gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('read_count')->label('Đã đọc')
                    ->formatStateUsing(fn ($state, NotificationModel $r) => $r->recipient_count ? $r->read_count.'/'.$r->recipient_count : '—'),
                TextColumn::make('publish_at')->label('Lịch/Phát hành')
                    ->formatStateUsing(fn ($state, NotificationModel $r) => ($r->published_at ?? $r->publish_at)?->format('d/m/Y H:i') ?? '—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('type')->label('Loại')->options(self::TYPE),
                SelectFilter::make('owner_level')->label('Cấp')->options(NotificationModel::OWNER_LEVEL),
            ])
            ->recordActions([
                $this->viewAction(),
                Action::make('publish')
                    ->label('Phát hành')->iconButton()->icon('heroicon-m-paper-airplane')->color('success')
                    ->visible(fn (NotificationModel $r) => in_array($r->status, ['draft', 'scheduled'], true) && $r->canManageBy(auth()->user()))
                    ->requiresConfirmation()
                    ->action(function (NotificationModel $r): void {
                        $this->applyPublish($r->load('audiences'));
                        $this->audit('notification.publish', 'Phát hành: '.$r->title, NotificationModel::class, $r->id);
                        Notification::make()->title('Đã phát hành ('.number_format($r->fresh()->recipient_count).' người nhận)')->success()->send();
                    }),
                Action::make('archive')
                    ->label('Lưu trữ')->iconButton()->icon('heroicon-m-archive-box')->color('gray')
                    ->visible(fn (NotificationModel $r) => $r->status !== 'archived' && $r->canManageBy(auth()->user()))
                    ->requiresConfirmation()
                    ->action(function (NotificationModel $r): void {
                        $r->update(['status' => 'archived']);
                        $this->audit('notification.archive', 'Lưu trữ: '.$r->title, NotificationModel::class, $r->id);
                        Notification::make()->title('Đã lưu trữ')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có thông báo')
            ->emptyStateIcon('heroicon-o-megaphone')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (NotificationModel $r) => $r->title)
            ->modalContent(fn (NotificationModel $r) => view('filament.pages.notification-detail', [
                'record' => $r->load(['audiences', 'channels', 'deliveryLogs']),
            ]))
            ->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }
}
