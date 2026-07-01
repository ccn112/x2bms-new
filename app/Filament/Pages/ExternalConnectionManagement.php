<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesIntegrationAudit;
use App\Models\IntegrationCategory;
use App\Models\IntegrationConnection;
use App\Models\IntegrationConnectionCheck;
use App\Models\IntegrationCredential;
use App\Support\Integration\IntegrationSecret;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * WEB-UX-28-02/03 — External Connection Management + Connection Detail.
 *
 * Danh sách kết nối bên ngoài + tạo/test/bật/tắt/rotate secret. Chi tiết (credential
 * che, mapping, health, checks, audit) mở trong modal. Secret hiện MỘT LẦN khi rotate.
 * Mọi hành động ghi integration_audit_logs.
 */
class ExternalConnectionManagement extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesIntegrationAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static ?string $navigationLabel = 'Kết nối bên ngoài';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Quản lý kết nối bên ngoài';

    protected static ?string $slug = 'integrations/connections';

    protected string $view = 'filament.pages.integration-connection-management';

    public const STATUS = [
        'active' => ['Hoạt động', 'success'], 'warning' => ['Cảnh báo', 'warning'],
        'incident' => ['Sự cố', 'danger'], 'disabled' => ['Đã tắt', 'gray'], 'archived' => ['Lưu trữ', 'gray'],
    ];

    protected function getViewData(): array
    {
        return [
            'kpis' => [
                ['label' => 'Tổng kết nối', 'value' => IntegrationConnection::count(), 'accent' => 'blue'],
                ['label' => 'Hoạt động', 'value' => IntegrationConnection::where('status', 'active')->count(), 'accent' => 'green'],
                ['label' => 'Cảnh báo', 'value' => IntegrationConnection::where('status', 'warning')->count(), 'accent' => 'amber'],
                ['label' => 'Sự cố', 'value' => IntegrationConnection::where('status', 'incident')->count(), 'accent' => 'red'],
                ['label' => 'Đã tắt', 'value' => IntegrationConnection::where('status', 'disabled')->count(), 'accent' => 'gray'],
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Tạo kết nối')->icon('heroicon-m-plus')->color('primary')
                ->modalHeading('Tạo kết nối mới')->modalWidth('xl')
                ->schema([
                    Select::make('category_id')->label('Nhóm')
                        ->options(IntegrationCategory::orderBy('sort_order')->pluck('name', 'id'))->required(),
                    TextInput::make('name')->label('Tên kết nối')->required()->maxLength(150),
                    TextInput::make('provider_code')->label('Provider')->required()->maxLength(80),
                    Select::make('environment')->label('Môi trường')
                        ->options(['sandbox' => 'Sandbox', 'staging' => 'Staging', 'production' => 'Production'])
                        ->default('sandbox')->required(),
                    TextInput::make('base_url')->label('Base URL')->url()->maxLength(255),
                    TextInput::make('timeout_seconds')->label('Timeout (s)')->numeric()->default(30),
                    Select::make('retry_policy')->label('Retry policy')
                        ->options(['fixed_3_attempts' => 'Fixed 3', 'exponential_5_attempts' => 'Exponential 5', 'exponential_10_attempts' => 'Exponential 10', 'manual_only' => 'Manual only'])
                        ->default('exponential_5_attempts'),
                    Toggle::make('idempotency_enabled')->label('Bật idempotency')->default(true),
                ])
                ->action(function (array $data): void {
                    $conn = IntegrationConnection::create($data + [
                        'code' => 'CONN-'.strtoupper(Str::random(6)),
                        'status' => 'disabled', 'sla_status' => 'healthy', 'owner_user_id' => auth()->id(),
                    ]);
                    $this->integrationAudit('connection.created', $conn, after: $conn->only(['code', 'name', 'environment']));
                    Notification::make()->title('Đã tạo kết nối (đang tắt — hãy test trước khi bật)')->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(IntegrationConnection::query()->with('category'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Tên kết nối')->searchable()->weight('medium')->color('primary')
                    ->description(fn (IntegrationConnection $r) => $r->code)->action($this->detailAction()),
                TextColumn::make('category.name')->label('Nhóm')->badge()->color('gray'),
                TextColumn::make('environment')->label('Môi trường')->badge()
                    ->color(fn (string $state) => $state === 'production' ? 'success' : 'gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('api_version')->label('API')->toggleable(),
                TextColumn::make('success_rate_24h')->label('Success 24h')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 1).'%' : '—'),
                TextColumn::make('last_checked_at')->label('Kiểm tra cuối')->since()->toggleable(),
                TextColumn::make('owner.name')->label('Owner')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('environment')->label('Môi trường')->options(['sandbox' => 'Sandbox', 'staging' => 'Staging', 'production' => 'Production']),
                SelectFilter::make('category_id')->label('Nhóm')->options(IntegrationCategory::pluck('name', 'id')),
            ])
            ->recordActions([
                $this->detailAction(),
                Action::make('test')
                    ->label('Test')->iconButton()->icon('heroicon-m-signal')->color('info')
                    ->requiresConfirmation()->modalDescription('Gửi request kiểm tra kết nối?')
                    ->action(fn (IntegrationConnection $r) => $this->runCheck($r)),
                Action::make('toggle')
                    ->iconButton()
                    ->icon(fn (IntegrationConnection $r) => $r->status === 'disabled' ? 'heroicon-m-play' : 'heroicon-m-pause')
                    ->color(fn (IntegrationConnection $r) => $r->status === 'disabled' ? 'success' : 'gray')
                    ->tooltip(fn (IntegrationConnection $r) => $r->status === 'disabled' ? 'Bật' : 'Tắt')
                    ->requiresConfirmation()
                    ->action(function (IntegrationConnection $r): void {
                        $before = $r->status;
                        $r->update(['status' => $r->status === 'disabled' ? 'active' : 'disabled']);
                        $this->integrationAudit($r->status === 'active' ? 'connection.enabled' : 'connection.disabled', $r, ['status' => $before], ['status' => $r->status]);
                        Notification::make()->title($r->status === 'active' ? 'Đã bật kết nối' : 'Đã tắt kết nối')->success()->send();
                    }),
                Action::make('rotate')
                    ->label('Rotate secret')->iconButton()->icon('heroicon-m-key')->color('warning')
                    ->requiresConfirmation()->modalDescription('Tạo secret mới? Secret cũ sẽ bị vô hiệu và secret mới chỉ hiện MỘT LẦN.')
                    ->action(fn (IntegrationConnection $r) => $this->rotateSecret($r)),
            ])
            ->emptyStateHeading('Chưa có kết nối')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function detailAction(): Action
    {
        return Action::make('detail')
            ->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (IntegrationConnection $r) => $r->name)
            ->modalContent(fn (IntegrationConnection $r) => view('filament.pages.integration-connection-detail', [
                'record' => $r->load(['category', 'owner', 'credentials', 'mappings', 'checks' => fn ($q) => $q->latest('checked_at')->limit(8)]),
                'audits' => \App\Models\IntegrationAuditLog::where('connection_id', $r->id)->latest('created_at')->limit(8)->get(),
            ]))
            ->modalWidth('3xl')->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    private function runCheck(IntegrationConnection $r): void
    {
        // Simulated health probe: healthy connections pass, incident ones fail.
        $ok = $r->status !== 'incident';
        $latency = random_int(80, 900);
        IntegrationConnectionCheck::create([
            'connection_id' => $r->id, 'status' => $ok ? 'success' : 'failed',
            'latency_ms' => $latency, 'http_status' => $ok ? 200 : 500,
            'message' => $ok ? 'OK' : 'Connection failed', 'checked_at' => now(), 'checked_by' => auth()->id(),
            'created_at' => now(),
        ]);
        $r->update(['last_checked_at' => now(), 'avg_latency_ms' => $latency]);
        $this->integrationAudit('connection.tested', $r, after: ['result' => $ok ? 'success' : 'failed', 'latency_ms' => $latency]);
        Notification::make()->title($ok ? "Test thành công ({$latency}ms)" : 'Test thất bại (HTTP 500)')->{$ok ? 'success' : 'danger'}()->send();
    }

    private function rotateSecret(IntegrationConnection $r): void
    {
        $secret = app(IntegrationSecret::class);
        $plain = $secret->generateApiSecret();

        IntegrationCredential::where('connection_id', $r->id)->where('status', 'valid')->update(['status' => 'rotated', 'rotated_at' => now(), 'rotated_by' => auth()->id()]);
        IntegrationCredential::create([
            'connection_id' => $r->id, 'credential_type' => 'api_key',
            'encrypted_payload' => $secret->encrypt($plain), 'masked_summary' => $secret->mask($plain),
            'status' => 'valid', 'expires_at' => now()->addDays(90), 'created_by' => auth()->id(),
        ]);
        $this->integrationAudit('connection.secret_rotated', $r, reason: 'manual rotation');

        // Show once — not persisted in retrievable form.
        Notification::make()->title('Secret mới (chỉ hiện MỘT LẦN)')->body($plain)->persistent()->success()->send();
    }
}
