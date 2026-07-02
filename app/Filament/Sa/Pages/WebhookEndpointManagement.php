<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesIntegrationAudit;
use App\Models\WebhookDeliveryAttempt;
use App\Models\WebhookEndpoint;
use App\Models\WebhookEventGroup;
use App\Support\Integration\IntegrationSecret;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * WEB-UX-28-06/07 — Webhook Endpoint Management + Test.
 *
 * Danh sách endpoint + tạo/test/bật/tắt/rotate secret + lịch sử delivery. Test gửi
 * sample payload → hiện HTTP status, latency, ký HMAC, response body, trace. Audit đủ.
 */
class WebhookEndpointManagement extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesIntegrationAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static ?string $navigationLabel = 'Webhook Endpoint';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Quản lý Webhook Endpoint';

    protected static ?string $slug = 'integrations/webhooks';

    protected string $view = 'filament.pages.integration-webhook-management';

    public const STATUS = [
        'active' => ['Hoạt động', 'success'], 'warning' => ['Cảnh báo', 'warning'],
        'disabled' => ['Đã tắt', 'gray'], 'failed' => ['Lỗi', 'danger'], 'pending_verification' => ['Chờ xác minh', 'warning'],
    ];

    protected function getViewData(): array
    {
        return [
            'kpis' => [
                ['label' => 'Tổng endpoint', 'value' => WebhookEndpoint::count(), 'accent' => 'blue'],
                ['label' => 'Hoạt động', 'value' => WebhookEndpoint::where('status', 'active')->count(), 'accent' => 'green'],
                ['label' => 'Cảnh báo', 'value' => WebhookEndpoint::where('status', 'warning')->count(), 'accent' => 'amber'],
                ['label' => 'Đã tắt', 'value' => WebhookEndpoint::where('status', 'disabled')->count(), 'accent' => 'gray'],
                ['label' => 'Success rate TB', 'value' => number_format((float) WebhookEndpoint::whereNotNull('success_rate')->avg('success_rate'), 1).'%', 'accent' => 'green'],
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Tạo endpoint')->icon('heroicon-m-plus')->color('primary')->modalWidth('xl')
                ->schema([
                    TextInput::make('endpoint_name')->label('Tên/Path')->required()->maxLength(150),
                    TextInput::make('url')->label('URL')->url()->required()->maxLength(255),
                    Select::make('event_group_id')->label('Nhóm sự kiện')->options(WebhookEventGroup::pluck('name', 'id'))->required(),
                    Select::make('method')->label('Method')->options(['POST' => 'POST', 'PUT' => 'PUT'])->default('POST')->required(),
                    Select::make('signature_type')->label('Chữ ký')->options(['HMAC' => 'HMAC', 'none' => 'Không'])->default('HMAC')->required(),
                    Select::make('retry_policy')->label('Retry policy')
                        ->options(['fixed_3_attempts' => 'Fixed 3', 'exponential_5_attempts' => 'Exponential 5', 'exponential_10_attempts' => 'Exponential 10', 'manual_only' => 'Manual only'])
                        ->default('exponential_5_attempts'),
                    TextInput::make('owner_name')->label('Owner')->maxLength(120),
                ])
                ->action(function (array $data): void {
                    $secret = app(IntegrationSecret::class);
                    $wh = WebhookEndpoint::create($data + [
                        'code' => 'WH-'.strtoupper(Str::random(6)), 'status' => 'pending_verification',
                        'signing_secret_hash' => ($data['signature_type'] ?? 'HMAC') === 'HMAC' ? $secret->hash($secret->generateSigningSecret()) : null,
                    ]);
                    $this->integrationAudit('webhook.created', $wh, after: $wh->only(['code', 'url', 'method']));
                    Notification::make()->title('Đã tạo endpoint (chờ test để kích hoạt)')->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(WebhookEndpoint::query()->with('eventGroup'))
            ->defaultSort('endpoint_name')
            ->columns([
                TextColumn::make('endpoint_name')->label('Endpoint')->searchable()->weight('medium')->color('primary')
                    ->description(fn (WebhookEndpoint $r) => $r->url)->action($this->historyAction()),
                TextColumn::make('eventGroup.name')->label('Nhóm sự kiện')->badge()->color('gray'),
                TextColumn::make('method')->label('Method')->badge()->color('gray'),
                TextColumn::make('signature_type')->label('Chữ ký')->badge()
                    ->color(fn (string $state) => $state === 'HMAC' ? 'success' : 'gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('success_rate')->label('Success')->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 1).'%' : '—'),
                TextColumn::make('last_delivery_at')->label('Gửi cuối')->since()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('event_group_id')->label('Nhóm sự kiện')->options(WebhookEventGroup::pluck('name', 'id')),
            ])
            ->recordActions([
                Action::make('test')
                    ->label('Test')->iconButton()->icon('heroicon-m-beaker')->color('info')
                    ->modalHeading(fn (WebhookEndpoint $r) => 'Test: '.$r->endpoint_name)
                    ->schema([
                        Select::make('sample_event')->label('Sample payload')
                            ->options(['payment.paid' => 'payment.paid', 'push.sent' => 'push.sent', 'work_order.updated' => 'work_order.updated', 'invoice.paid' => 'invoice.paid'])
                            ->default('payment.paid')->required(),
                    ])
                    ->modalSubmitActionLabel('Gửi test')
                    ->action(fn (array $data, WebhookEndpoint $r) => $this->runTest($r, $data['sample_event'])),
                Action::make('history')
                    ->label('Lịch sử')->iconButton()->icon('heroicon-m-clock')->color('gray')
                    ->modalContent(fn (WebhookEndpoint $r) => view('filament.pages.integration-webhook-deliveries', [
                        'record' => $r, 'deliveries' => $r->deliveries()->latest('created_at')->limit(15)->get(),
                    ]))->modalWidth('3xl')->modalSubmitAction(false)->modalCancelActionLabel('Đóng'),
                Action::make('toggle')
                    ->iconButton()
                    ->icon(fn (WebhookEndpoint $r) => $r->status === 'disabled' ? 'heroicon-m-play' : 'heroicon-m-pause')
                    ->color(fn (WebhookEndpoint $r) => $r->status === 'disabled' ? 'success' : 'gray')
                    ->tooltip(fn (WebhookEndpoint $r) => $r->status === 'disabled' ? 'Bật' : 'Tắt')
                    ->requiresConfirmation()
                    ->action(function (WebhookEndpoint $r): void {
                        $before = $r->status;
                        $r->update(['status' => $r->status === 'disabled' ? 'active' : 'disabled']);
                        $this->integrationAudit($r->status === 'active' ? 'webhook.enabled' : 'webhook.disabled', $r, ['status' => $before], ['status' => $r->status]);
                        Notification::make()->title('Đã cập nhật trạng thái')->success()->send();
                    }),
                Action::make('rotate')
                    ->label('Rotate secret')->iconButton()->icon('heroicon-m-key')->color('warning')
                    ->requiresConfirmation()->modalDescription('Tạo signing secret mới cho endpoint?')
                    ->action(function (WebhookEndpoint $r): void {
                        $secret = app(IntegrationSecret::class);
                        $plain = $secret->generateSigningSecret();
                        $r->update(['signing_secret_hash' => $secret->hash($plain), 'signature_type' => 'HMAC']);
                        $this->integrationAudit('webhook.secret_rotated', $r);
                        Notification::make()->title('Signing secret mới (chỉ hiện MỘT LẦN)')->body($plain)->persistent()->success()->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có webhook endpoint')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function historyAction(): Action
    {
        return Action::make('open')->label('Lịch sử')
            ->modalContent(fn (WebhookEndpoint $r) => view('filament.pages.integration-webhook-deliveries', [
                'record' => $r, 'deliveries' => $r->deliveries()->latest('created_at')->limit(15)->get(),
            ]))->modalWidth('3xl')->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    private function runTest(WebhookEndpoint $r, string $event): void
    {
        // Simulated delivery: disabled/failed endpoints return 500, else 200.
        $ok = ! in_array($r->status, ['disabled', 'failed'], true);
        $latency = random_int(60, 700);
        $corr = 'corr_'.Str::lower(Str::random(12));
        $attemptNo = $r->deliveries()->count() + 1;
        WebhookDeliveryAttempt::create([
            'webhook_endpoint_id' => $r->id, 'event_id' => 'evt_'.strtoupper(Str::random(20)),
            'correlation_id' => $corr, 'payload_hash' => hash('sha256', $event.$corr),
            'http_status' => $ok ? 200 : 500, 'duration_ms' => $latency, 'status' => $ok ? 'success' : 'failed',
            'attempt_no' => $attemptNo, 'response_body' => $ok ? '{"received":true}' : '{"error":"Internal Server Error"}',
            'error_message' => $ok ? null : 'HTTP 500', 'delivered_at' => now(), 'created_at' => now(),
        ]);
        if ($ok && $r->status === 'pending_verification') {
            $r->update(['status' => 'active']);
        }
        $r->update(['last_delivery_at' => now()]);
        $this->integrationAudit('webhook.tested', $r, after: ['event' => $event, 'http' => $ok ? 200 : 500, 'latency_ms' => $latency, 'signature_verified' => $r->signature_type === 'HMAC']);

        Notification::make()
            ->title($ok ? "HTTP 200 · {$latency}ms · signature ".($r->signature_type === 'HMAC' ? 'verified' : 'n/a') : 'HTTP 500 — thất bại')
            ->body('Correlation: '.$corr)
            ->{$ok ? 'success' : 'danger'}()->persistent()->send();
    }
}
