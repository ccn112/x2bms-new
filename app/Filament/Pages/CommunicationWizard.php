<?php

namespace App\Filament\Pages;

use App\Enums\CommunicationContentType as CT;
use App\Enums\CommunicationSendStrategy;
use App\Enums\CommunicationWorkflowStatus as WS;
use App\Filament\Concerns\WritesAudit;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Notification as NotificationModel;
use App\Models\NotificationAudience;
use App\Models\NotificationChannel;
use App\Models\NotificationAudienceGroup;
use App\Models\Project;
use App\Models\Tenant;
use App\Services\Notifications\AudienceResolver;
use App\Services\Notifications\CampaignCostEstimator;
use App\Services\Notifications\CampaignStateMachine;
use App\Services\Notifications\ContentSubtypeService;
use App\Services\Notifications\NotificationApprovalService;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * BQL-NOTI-02→06 — Wizard soạn truyền thông 5 bước (nội dung → đối tượng → kênh →
 * hẹn giờ/duyệt → xem lại). Server draft: tạo notifications(workflow_status=draft) ở
 * mount, autosave từng bước. send_now KHÔNG bypass duyệt (locked decision): kết thúc
 * wizard là "Gửi duyệt" (hoặc lưu nháp). Duyệt & phát hành ở màn chi tiết (BQL-NOTI-07).
 *
 * Feature-flag x2.bql_wizard_enabled; giữ NotificationCenter (compose cũ) tới khi parity.
 * Dùng lại toàn bộ service domain (ADR-002) — Page chỉ orchestrate, không chứa business.
 */
class CommunicationWizard extends Page implements HasForms
{
    use InteractsWithForms;
    use WritesAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string|UnitEnum|null $navigationGroup = 'Vận hành';

    protected static ?string $navigationLabel = 'Tạo truyền thông';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'notifications/create';

    protected string $view = 'filament.pages.communication-wizard';

    public ?array $data = [];

    public ?int $recordId = null;

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('x2.bql_wizard_enabled', true);
    }

    public function getTitle(): string
    {
        return 'Tạo mới truyền thông';
    }

    public function mount(): void
    {
        abort_unless((bool) config('x2.bql_wizard_enabled', true), 404);

        $draft = NotificationModel::create($this->creatorOwner() + [
            'code' => 'NTF-'.strtoupper(Str::random(6)),
            'content_type' => CT::Announcement->value,
            'type' => 'announcement',
            'workflow_status' => WS::Draft->value,
            'status' => 'draft',
            'title' => 'Bản nháp truyền thông',
            'priority' => 'normal',
            'created_by_id' => auth()->id(),
        ]);
        $this->recordId = $draft->id;

        $this->form->fill([
            'content_type' => CT::Announcement->value,
            'priority' => 'normal',
            'channels' => ['app', 'push'],
            'send_strategy' => CommunicationSendStrategy::Parallel->value,
            'send_now' => false,
            'audience_scope' => array_key_first($this->scopeOptions()),
        ]);
    }

    protected function getForms(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Wizard::make([
                    $this->stepContent(),
                    $this->stepAudience(),
                    $this->stepChannels(),
                    $this->stepSchedule(),
                    $this->stepReview(),
                ])->persistStepInQueryString('step'),
            ]);
    }

    // ---- Step 1: content (BQL-NOTI-02) ----------------------------------

    private function stepContent(): Step
    {
        return Step::make('Nội dung')
            ->icon('heroicon-o-document-text')
            ->schema([
                ToggleButtons::make('content_type')->label('Loại nội dung')
                    ->options(collect(CT::cases())->mapWithKeys(fn (CT $c) => [$c->value => $c->label()])->all())
                    ->icons(collect(CT::cases())->mapWithKeys(fn (CT $c) => [$c->value => $c->icon()])->all())
                    ->inline()->live()->default(CT::Announcement->value)->required(),

                TextInput::make('title')->label('Tiêu đề')->required()->maxLength(255)->columnSpanFull(),
                Textarea::make('summary')->label('Tóm tắt')->rows(2)->maxLength(500)->columnSpanFull(),
                Select::make('category')->label('Nhóm nghiệp vụ')->options($this->categoryOptions())->searchable(),
                Select::make('priority')->label('Mức ưu tiên')
                    ->options(['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'])
                    ->default('normal')->required(),
                FileUpload::make('cover_path')->label('Ảnh bìa')->image()->directory('notifications/covers')->imageEditor()->columnSpanFull(),
                RichEditor::make('body')->label('Nội dung chi tiết')
                    ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link', 'h2', 'h3', 'undo', 'redo'])
                    ->columnSpanFull(),

                // Field động theo loại (spec 04).
                $this->newsFields(),
                $this->eventFields(),
                $this->pollFields(),

                Section::make('Tùy chọn hiển thị')->columns(3)->schema([
                    Toggle::make('requires_ack')->label('Yêu cầu xác nhận đã đọc'),
                    Toggle::make('allow_feedback')->label('Cho phép phản hồi'),
                    Toggle::make('is_pinned')->label('Ghim trên app'),
                    TextInput::make('cta_label')->label('Nhãn nút CTA')->maxLength(60),
                    TextInput::make('cta_target')->label('Liên kết CTA')->maxLength(255),
                    DateTimePicker::make('expires_at')->label('Hết hạn hiển thị'),
                ]),
            ])->columns(2);
    }

    private function newsFields(): Section
    {
        return Section::make('Thông tin tin tức')
            ->visible(fn (Get $get) => $get('content_type') === CT::News->value)
            ->columns(2)
            ->schema([
                TextInput::make('news_author')->label('Tác giả'),
                Select::make('news_visibility')->label('Phạm vi hiển thị')
                    ->options(['resident' => 'Chỉ cư dân', 'public' => 'Công khai'])->default('resident'),
                Toggle::make('news_featured')->label('Nổi bật'),
            ]);
    }

    private function eventFields(): Section
    {
        return Section::make('Thông tin sự kiện')
            ->visible(fn (Get $get) => $get('content_type') === CT::Event->value)
            ->columns(2)
            ->schema([
                TextInput::make('event_venue')->label('Địa điểm')->required(fn (Get $get) => $get('content_type') === CT::Event->value),
                DateTimePicker::make('event_starts_at')->label('Bắt đầu')->required(fn (Get $get) => $get('content_type') === CT::Event->value),
                TextInput::make('event_duration_minutes')->label('Thời lượng (phút)')->numeric()->default(120),
                TextInput::make('event_capacity')->label('Sức chứa')->numeric(),
                DateTimePicker::make('event_registration_deadline')->label('Hạn đăng ký'),
                TextInput::make('event_fee_amount')->label('Phí (VND)')->numeric()->default(0),
                Toggle::make('event_allow_guests')->label('Cho phép khách'),
                Toggle::make('event_qr_checkin')->label('Check-in QR'),
            ]);
    }

    private function pollFields(): Section
    {
        return Section::make('Thông tin bình chọn')
            ->visible(fn (Get $get) => $get('content_type') === CT::Poll->value)
            ->columns(2)
            ->schema([
                TextInput::make('poll_question')->label('Câu hỏi')->columnSpanFull()
                    ->required(fn (Get $get) => $get('content_type') === CT::Poll->value),
                Select::make('poll_vote_scope')->label('Phạm vi phiếu')
                    ->options(['resident' => '1 phiếu / cư dân', 'apartment' => '1 phiếu / căn hộ'])->default('resident'),
                Select::make('poll_result_visibility')->label('Hiển thị kết quả')
                    ->options(['after_vote' => 'Sau khi bình chọn', 'after_close' => 'Sau khi đóng', 'public_after_close' => 'Công khai sau khi đóng', 'admin_only' => 'Chỉ BQL'])
                    ->default('after_vote'),
                Toggle::make('poll_allow_multiple')->label('Chọn nhiều'),
                Toggle::make('poll_anonymous')->label('Ẩn danh'),
                Toggle::make('poll_allow_change_vote')->label('Cho đổi phiếu'),
                DateTimePicker::make('poll_closes_at')->label('Đóng lúc'),
                Repeater::make('poll_options')->label('Lựa chọn')->columnSpanFull()
                    ->schema([
                        TextInput::make('key')->label('Mã')->maxLength(8),
                        TextInput::make('label')->label('Nội dung lựa chọn')->required(),
                    ])->columns(2)->minItems(2)->defaultItems(2)
                    ->required(fn (Get $get) => $get('content_type') === CT::Poll->value),
            ]);
    }

    // ---- Step 2: audience (BQL-NOTI-03) ---------------------------------

    private function stepAudience(): Step
    {
        return Step::make('Đối tượng')
            ->icon('heroicon-o-user-group')
            ->schema([
                Select::make('audience_group_id')->label('Nhóm đã lưu (tùy chọn)')
                    ->options(fn () => NotificationAudienceGroup::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()->helperText('Chọn nhóm người nhận đã lưu, hoặc dựng phạm vi bên dưới.'),
                Select::make('audience_scope')->label('Phạm vi nhận')->options($this->scopeOptions())->required()->live()
                    ->default(array_key_first($this->scopeOptions())),
                Select::make('audience_target')->label('Chọn đối tượng')->searchable()->live()
                    ->options(fn (Get $get) => $this->audienceTargetOptions($get('audience_scope')))
                    ->visible(fn (Get $get) => in_array($get('audience_scope'), ['tenant', 'project', 'building', 'apartment'], true))
                    ->required(fn (Get $get) => in_array($get('audience_scope'), ['tenant', 'project', 'building', 'apartment'], true)),
                Select::make('include_roles')->label('Vai trò với căn hộ')->multiple()
                    ->options(['owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'member' => 'Thành viên'])
                    ->helperText('Bỏ trống = mọi vai trò.'),
                Toggle::make('audience_locked')->label('Khóa danh sách người nhận sau khi chốt'),
                Placeholder::make('estimate')->hiddenLabel()
                    ->content(fn (Get $get) => $this->estimateText($get))->columnSpanFull(),
            ])->columns(2);
    }

    // ---- Step 3: channels (BQL-NOTI-04) ---------------------------------

    private function stepChannels(): Step
    {
        return Step::make('Kênh gửi')
            ->icon('heroicon-o-signal')
            ->schema([
                CheckboxList::make('channels')->label('Kênh gửi')->columns(3)->required()
                    ->options([
                        'app' => 'App (hộp thư)', 'push' => 'Đẩy về máy (Push)', 'email' => 'Email',
                        'sms' => 'SMS', 'zalo' => 'Zalo OA', 'web' => 'Web cư dân',
                    ])
                    ->descriptions([
                        'app' => 'Nguồn hiển thị bền vững', 'push' => 'Cần cài app', 'email' => 'Gửi thật (Elastic Email)',
                        'sms' => 'Có phí · nhà cung cấp chờ cấu hình', 'zalo' => 'Có phí · cổng chờ theo tòa', 'web' => 'Cổng thông tin cư dân',
                    ])->live(),
                Select::make('send_strategy')->label('Chiến lược gửi')
                    ->options(collect(CommunicationSendStrategy::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())
                    ->default(CommunicationSendStrategy::Parallel->value)->required(),
                Placeholder::make('cost')->hiddenLabel()
                    ->content(fn (Get $get) => $this->costText($get))->columnSpanFull(),
            ])->columns(2);
    }

    // ---- Step 4: schedule + approval (BQL-NOTI-05) ----------------------

    private function stepSchedule(): Step
    {
        return Step::make('Hẹn giờ & duyệt')
            ->icon('heroicon-o-clock')
            ->schema([
                Toggle::make('send_now')->label('Gửi ngay sau khi được duyệt')->live()->default(false),
                DateTimePicker::make('publish_at')->label('Hẹn giờ phát hành')
                    ->visible(fn (Get $get) => ! $get('send_now'))
                    ->helperText('Lưu theo giờ Việt Nam ('.config('x2.timezone').').'),
                Placeholder::make('approval_route')->hiddenLabel()
                    ->content(fn (Get $get) => $this->approvalRouteText($get))->columnSpanFull(),
            ])->columns(2);
    }

    // ---- Step 5: review (BQL-NOTI-06) -----------------------------------

    private function stepReview(): Step
    {
        return Step::make('Xem lại')
            ->icon('heroicon-o-check-circle')
            ->schema([
                Placeholder::make('preflight')->hiddenLabel()
                    ->content(fn (Get $get) => new \Illuminate\Support\HtmlString(nl2br(e($this->preflightText($get)))))
                    ->columnSpanFull(),
            ]);
    }

    // ---- Actions --------------------------------------------------------

    public function saveDraft(): void
    {
        $this->persist();
        Notification::make()->title('Đã lưu nháp')->info()->send();
    }

    public function submitForApproval(): void
    {
        $n = $this->persist();

        if ((int) $n->recipient_count === 0) {
            Notification::make()->title('Chưa có người nhận — kiểm tra lại đối tượng')->warning()->send();

            return;
        }

        app(NotificationApprovalService::class)->requestApproval($n, auth()->id());
        $this->audit('notification.submit_approval', 'Gửi duyệt truyền thông: '.$n->title, NotificationModel::class, $n->id);

        Notification::make()->title('Đã gửi duyệt')->body('Chiến dịch chuyển sang chờ duyệt.')->success()->send();
        $this->redirect(NotificationCenter::getUrl());
    }

    /** Ghi toàn bộ state vào draft (idempotent): nội dung + subtype + audience + kênh + cost. */
    private function persist(): NotificationModel
    {
        $state = $this->form->getState();
        $n = NotificationModel::findOrFail($this->recordId);
        abort_unless($n->canManageBy(auth()->user()), 403);

        $type = CT::from($state['content_type']);
        app(ContentSubtypeService::class)->validate($type, $this->subtypeData($type, $state));

        $n->fill([
            'content_type' => $type->value,
            'type' => $type === CT::Announcement ? 'announcement' : $type->value,
            'title' => $state['title'] ?? $n->title,
            'summary' => $state['summary'] ?? null,
            'body' => $state['body'] ?? null,
            'category' => $state['category'] ?? null,
            'priority' => $state['priority'] ?? 'normal',
            'cover_path' => $state['cover_path'] ?? null,
            'requires_ack' => (bool) ($state['requires_ack'] ?? false),
            'allow_feedback' => (bool) ($state['allow_feedback'] ?? false),
            'is_pinned' => (bool) ($state['is_pinned'] ?? false),
            'cta_label' => $state['cta_label'] ?? null,
            'cta_target' => $state['cta_target'] ?? null,
            'expires_at' => $state['expires_at'] ?? null,
            'send_strategy' => $state['send_strategy'] ?? CommunicationSendStrategy::Parallel->value,
            'publish_at' => ($state['send_now'] ?? false) ? null : ($state['publish_at'] ?? null),
            'audience_rule' => $this->buildRule($state),
            'audience_locked' => (bool) ($state['audience_locked'] ?? false),
        ]);

        // Subtype: tạo/link Event/Poll canonical + news meta.
        app(ContentSubtypeService::class)->syncEntity($n, $type, $this->subtypeData($type, $state));
        $n->save();

        // Kênh (replace).
        $n->channels()->delete();
        foreach (($state['channels'] ?? ['app']) as $ch) {
            NotificationChannel::create(['notification_id' => $n->id, 'channel' => $ch, 'enabled' => true]);
        }

        // Audience row (compat dispatchers cũ) + resolve recipients (dedupe + snapshot).
        $n->audiences()->delete();
        [$scopeType, $scopeId] = $this->scopeRow($state);
        NotificationAudience::create(['notification_id' => $n->id, 'scope_type' => $scopeType, 'scope_id' => $scopeId]);

        $n->load('channels');
        $count = app(AudienceResolver::class)->resolve($n->fresh('channels'));

        // Cost estimate theo giá kênh.
        $cost = app(CampaignCostEstimator::class)->estimate($state['channels'] ?? [], $count);
        $n->forceFill(['cost_estimate' => $cost['total']])->save();

        return $n->fresh();
    }

    // ---- helpers: scope/estimate/cost/approval/preflight ----------------

    /** @return array{0:string,1:int|null} */
    private function scopeRow(array $state): array
    {
        $scope = $state['audience_scope'] ?? 'project';
        if ($scope === 'tenant' || $scope === 'project') {
            // dùng tenant/project của chiến dịch; audiences row giữ scope + target hiển thị.
            return [$scope, (int) ($state['audience_target'] ?? 0) ?: null];
        }

        return [$scope, (int) ($state['audience_target'] ?? 0) ?: null];
    }

    /** @return array<string,mixed> */
    private function buildRule(array $state): array
    {
        $scope = $state['audience_scope'] ?? 'project';
        $target = (int) ($state['audience_target'] ?? 0);
        $rule = ['scope' => [], 'include' => [], 'exclude' => []];

        $rule['scope'] = match ($scope) {
            'building' => ['building_ids' => [$target]],
            'apartment' => ['apartment_ids' => [$target]],
            default => [], // tenant/project scope enforced by notification tenant_id/project_id
        };
        if (! empty($state['include_roles'])) {
            $rule['include'][] = ['field' => 'relationship_roles', 'operator' => 'in', 'value' => $state['include_roles']];
        }

        return $rule;
    }

    /** @return array<string,mixed> */
    private function subtypeData(CT $type, array $state): array
    {
        return match ($type) {
            CT::Event => [
                'venue' => $state['event_venue'] ?? null,
                'starts_at' => $state['event_starts_at'] ?? null,
                'duration_minutes' => $state['event_duration_minutes'] ?? null,
                'capacity' => $state['event_capacity'] ?? null,
                'registration_deadline' => $state['event_registration_deadline'] ?? null,
                'fee_amount' => $state['event_fee_amount'] ?? 0,
                'allow_guests' => $state['event_allow_guests'] ?? false,
                'qr_checkin' => $state['event_qr_checkin'] ?? false,
            ],
            CT::Poll => [
                'question' => $state['poll_question'] ?? $state['title'] ?? '',
                'options' => $state['poll_options'] ?? [],
                'allow_multiple' => $state['poll_allow_multiple'] ?? false,
                'vote_scope' => $state['poll_vote_scope'] ?? 'resident',
                'anonymous' => $state['poll_anonymous'] ?? false,
                'allow_change_vote' => $state['poll_allow_change_vote'] ?? false,
                'result_visibility' => $state['poll_result_visibility'] ?? 'after_vote',
                'closes_at' => $state['poll_closes_at'] ?? null,
            ],
            CT::News => [
                'category' => $state['category'] ?? 'news',
                'author' => $state['news_author'] ?? null,
                'visibility' => $state['news_visibility'] ?? 'resident',
                'featured' => $state['news_featured'] ?? false,
            ],
            CT::Announcement => [],
        };
    }

    private function estimateText(Get $get): string
    {
        $n = $this->recordId ? NotificationModel::find($this->recordId) : null;
        if (! $n) {
            return 'Ước tính người nhận sẽ hiện sau khi chọn phạm vi.';
        }
        $rule = $this->buildRule([
            'audience_scope' => $get('audience_scope'),
            'audience_target' => $get('audience_target'),
            'include_roles' => $get('include_roles'),
        ]);
        // project scope cần project_id trên notification (đã có từ creatorOwner).
        $est = app(AudienceResolver::class)->estimate($n, $rule);

        return "📊 Ước tính: {$est['residents']} cư dân · {$est['apartments']} căn hộ (theo phạm vi & vai trò đã chọn).";
    }

    private function costText(Get $get): string
    {
        $channels = (array) $get('channels');
        $n = $this->recordId ? NotificationModel::find($this->recordId) : null;
        $count = $n ? (int) $n->recipient_count : 0;
        $cost = app(CampaignCostEstimator::class)->estimate($channels, $count);
        if ($cost['total'] === 0) {
            return '💸 Ước tính chi phí: 0đ (chỉ kênh miễn phí).';
        }

        return '💸 Ước tính chi phí: '.number_format($cost['total'], 0, ',', '.').'đ'
            .($cost['paid'] > 0 ? ' (kênh trả phí: '.number_format($cost['paid'], 0, ',', '.').'đ)' : '');
    }

    private function approvalRouteText(Get $get): string
    {
        $n = $this->recordId ? NotificationModel::find($this->recordId) : null;
        if (! $n) {
            return '';
        }
        try {
            $route = app(NotificationApprovalService::class)->resolveRoute($n);
            $steps = collect($route['steps'] ?? [])->pluck('role')->implode(' → ');

            return '🔐 Tuyến duyệt áp dụng: '.($route['name'] ?? $route['key']).' ('.$steps.'). Gửi ngay KHÔNG bỏ qua duyệt.';
        } catch (\Throwable) {
            return '';
        }
    }

    private function preflightText(Get $get): string
    {
        $n = $this->recordId ? NotificationModel::find($this->recordId) : null;
        $checks = [];
        $checks[] = filled($get('title')) ? '✅ Có tiêu đề' : '❌ Thiếu tiêu đề';
        $checks[] = ! empty((array) $get('channels')) ? '✅ Đã chọn kênh' : '❌ Chưa chọn kênh';
        $checks[] = ($n && (int) $n->recipient_count > 0)
            ? "✅ {$n->recipient_count} người nhận (đã resolve ở bước Đối tượng)"
            : 'ℹ️ Người nhận sẽ được chốt khi bấm Gửi duyệt';

        return "Kiểm tra trước phát hành:\n".implode("\n", $checks)
            ."\n\nBấm \"Gửi duyệt\" để chốt snapshot nội dung + người nhận và chuyển sang chờ duyệt.";
    }

    // ---- scope helpers (mirror NotificationCenter, theo quyền người soạn) ----

    private function scopeOptions(): array
    {
        $u = auth()->user();
        if ($u->isPlatformAdmin()) {
            return ['tenant' => 'Công ty', 'project' => 'Dự án', 'building' => 'Tòa nhà', 'apartment' => 'Căn hộ'];
        }
        if ($u->isTenantOperator()) {
            return ['project' => 'Dự án', 'building' => 'Tòa nhà', 'apartment' => 'Căn hộ'];
        }

        return ['building' => 'Tòa nhà', 'apartment' => 'Căn hộ'];
    }

    private function audienceTargetOptions(?string $scope): array
    {
        $u = auth()->user();
        $projectIds = (! $u->isPlatformAdmin() && ! $u->isTenantOperator())
            ? ($u->accessibleProjectIds() ?? [])
            : null;

        $buildings = fn () => Building::query()
            ->when($projectIds !== null, fn ($q) => $q->whereIn('project_id', $projectIds));

        return match ($scope) {
            'tenant' => ($u->isPlatformAdmin() ? Tenant::query() : Tenant::whereKey($u->tenant_id))
                ->orderBy('name')->pluck('name', 'id')->all(),
            'project' => Project::query()
                ->when($projectIds !== null, fn ($q) => $q->whereIn('id', $projectIds))
                ->orderBy('name')->pluck('name', 'id')->all(),
            'building' => $buildings()->orderBy('name')->pluck('name', 'id')->all(),
            'apartment' => Apartment::query()
                ->when($projectIds !== null, fn ($q) => $q->whereIn('building_id', $buildings()->select('id')))
                ->orderBy('code')->pluck('code', 'id')->all(),
            default => [],
        };
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

        return ['owner_level' => 'project', 'tenant_id' => $u->tenant_id,
            'project_id' => app(CurrentContext::class)->projectId() ?? $u->project_id];
    }

    private function categoryOptions(): array
    {
        return [
            'operations_technical' => 'Vận hành / kỹ thuật', 'security_safety' => 'An ninh / an toàn',
            'finance' => 'Tài chính', 'amenity' => 'Tiện ích', 'community_environment' => 'Cộng đồng / môi trường',
            'parking_access' => 'Gửi xe / ra vào', 'access' => 'Kiểm soát ra vào', 'customer_service' => 'CSKH',
            'digital_service' => 'Dịch vụ số', 'health' => 'Sức khỏe', 'education_health' => 'Giáo dục / sức khỏe',
            'community' => 'Cộng đồng', 'community_market' => 'Chợ cư dân', 'amenity_sports' => 'Thể thao / tiện ích',
        ];
    }
}
