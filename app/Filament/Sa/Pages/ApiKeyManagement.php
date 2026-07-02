<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesIntegrationAudit;
use App\Models\IntegrationApiKey;
use App\Models\IntegrationApiKeyRotation;
use App\Models\IntegrationApiKeyScope;
use App\Support\Integration\IntegrationSecret;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * WEB-UX-28-04/05 — API Key Management + Create/Detail.
 *
 * Tạo API key (client_id + scopes + rate limit + IP allowlist + HMAC), secret hiện
 * MỘT LẦN. Rotate/revoke/suspend/resume. Mọi hành động ghi integration_audit_logs.
 */
class ApiKeyManagement extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesIntegrationAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static ?string $navigationLabel = 'API Key';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Quản lý API Key';

    protected static ?string $slug = 'integrations/api-keys';

    protected string $view = 'filament.pages.integration-api-key-management';

    public const STATUS = [
        'draft' => ['Nháp', 'gray'], 'active' => ['Hoạt động', 'success'], 'expiring' => ['Sắp hết hạn', 'warning'],
        'revoked' => ['Thu hồi', 'danger'], 'expired' => ['Hết hạn', 'gray'], 'suspended' => ['Tạm ngưng', 'warning'],
    ];

    public const SCOPES = [
        'admin:full' => 'Admin: full', 'resident:read' => 'Resident: read', 'device:manage' => 'Device: manage',
        'device:read' => 'Device: read', 'events:read' => 'Events: read', 'webhooks:write' => 'Webhooks: write',
        'finance:write' => 'Finance: write', 'invoice:read' => 'Invoice: read', 'partner:read' => 'Partner: read',
        'purchase:read' => 'Purchase: read', 'delivery:read' => 'Delivery: read', 'telemetry:write' => 'Telemetry: write',
        'messaging:read' => 'Messaging: read',
    ];

    protected function getViewData(): array
    {
        return [
            'kpis' => [
                ['label' => 'Tổng key', 'value' => IntegrationApiKey::count(), 'accent' => 'blue'],
                ['label' => 'Hoạt động', 'value' => IntegrationApiKey::where('status', 'active')->count(), 'accent' => 'green'],
                ['label' => 'Sắp hết hạn', 'value' => IntegrationApiKey::where('status', 'expiring')->count(), 'accent' => 'amber'],
                ['label' => 'Thu hồi/Hết hạn', 'value' => IntegrationApiKey::whereIn('status', ['revoked', 'expired'])->count(), 'accent' => 'red'],
                ['label' => 'Tạm ngưng', 'value' => IntegrationApiKey::where('status', 'suspended')->count(), 'accent' => 'gray'],
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Tạo API Key')->icon('heroicon-m-plus')->color('primary')
                ->modalHeading('Tạo API Key mới')->modalWidth('xl')
                ->schema([
                    TextInput::make('name')->label('Tên')->required()->maxLength(150),
                    Select::make('environment')->label('Môi trường')
                        ->options(['sandbox' => 'Sandbox', 'staging' => 'Staging', 'production' => 'Production'])->default('production')->required(),
                    CheckboxList::make('scopes')->label('Scopes')->options(self::SCOPES)->columns(2)->required(),
                    TextInput::make('rate_limit_per_minute')->label('Rate limit / phút')->numeric()->default(600)->required(),
                    DatePicker::make('expires_at')->label('Hết hạn')->default(now()->addYear()),
                    Toggle::make('require_hmac')->label('Bắt buộc HMAC')->default(true),
                    Toggle::make('require_ip_allowlist')->label('Bắt buộc IP allowlist')->default(false)->live(),
                    Textarea::make('allowed_ips')->label('IP allowlist (mỗi dòng 1 IP/CIDR)')
                        ->visible(fn ($get) => $get('require_ip_allowlist')),
                ])
                ->action(fn (array $data) => $this->createKey($data)),
        ];
    }

    private function createKey(array $data): void
    {
        $secret = app(IntegrationSecret::class);
        $plain = $secret->generateApiSecret();
        $ips = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $data['allowed_ips'] ?? ''))));

        $key = IntegrationApiKey::create([
            'name' => $data['name'], 'client_id' => $secret->generateClientId(), 'secret_hash' => $secret->hash($plain),
            'environment' => $data['environment'], 'status' => 'active', 'expires_at' => $data['expires_at'] ?? null,
            'owner_user_id' => auth()->id(), 'rate_limit_per_minute' => $data['rate_limit_per_minute'],
            'require_hmac' => $data['require_hmac'] ?? false, 'require_ip_allowlist' => $data['require_ip_allowlist'] ?? false,
            'allowed_ips_json' => $ips ?: null, 'metadata_json' => ['secret_masked' => $secret->mask($plain)],
        ]);
        foreach (($data['scopes'] ?? []) as $sc) {
            [$res, $lvl] = array_pad(explode(':', $sc), 2, 'read');
            IntegrationApiKeyScope::create(['api_key_id' => $key->id, 'scope_code' => $sc, 'scope_name' => self::SCOPES[$sc] ?? $sc, 'permission_level' => $lvl]);
        }
        $this->integrationAudit('api_key.created', $key, after: ['client_id' => $key->client_id, 'scopes' => $data['scopes'] ?? []]);

        Notification::make()->title('API Key đã tạo')->body("Client ID: {$key->client_id}\nSecret (chỉ hiện 1 lần): {$plain}")->persistent()->success()->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(IntegrationApiKey::query()->withCount('scopes'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Tên')->searchable()->weight('medium')->color('primary')
                    ->description(fn (IntegrationApiKey $r) => $r->client_id)->action($this->detailAction()),
                TextColumn::make('scopes_count')->label('Scopes')->badge()->color('gray'),
                TextColumn::make('environment')->label('Môi trường')->badge()
                    ->color(fn (string $state) => $state === 'production' ? 'success' : 'gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('rate_limit_per_minute')->label('Rate limit')->suffix('/phút'),
                IconColumn::make('require_hmac')->label('HMAC')->boolean(),
                TextColumn::make('expires_at')->label('Hết hạn')->date('d/m/Y')->toggleable(),
                TextColumn::make('last_used_at')->label('Last used')->since()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('environment')->label('Môi trường')->options(['sandbox' => 'Sandbox', 'staging' => 'Staging', 'production' => 'Production']),
            ])
            ->recordActions([
                $this->detailAction(),
                Action::make('rotate')
                    ->label('Rotate')->iconButton()->icon('heroicon-m-arrow-path')->color('warning')
                    ->visible(fn (IntegrationApiKey $r) => in_array($r->status, ['active', 'expiring', 'suspended'], true))
                    ->requiresConfirmation()->modalDescription('Tạo secret mới? Secret cũ ngừng hiệu lực, secret mới chỉ hiện MỘT LẦN.')
                    ->action(fn (IntegrationApiKey $r) => $this->rotate($r)),
                Action::make('suspend')
                    ->label('Tạm ngưng')->iconButton()->icon('heroicon-m-pause')->color('gray')
                    ->visible(fn (IntegrationApiKey $r) => $r->status === 'active')
                    ->requiresConfirmation()
                    ->action(function (IntegrationApiKey $r): void {
                        $r->update(['status' => 'suspended']);
                        $this->integrationAudit('api_key.suspended', $r);
                        Notification::make()->title('Đã tạm ngưng')->success()->send();
                    }),
                Action::make('resume')
                    ->label('Kích hoạt lại')->iconButton()->icon('heroicon-m-play')->color('success')
                    ->visible(fn (IntegrationApiKey $r) => $r->status === 'suspended')
                    ->requiresConfirmation()
                    ->action(function (IntegrationApiKey $r): void {
                        $r->update(['status' => 'active']);
                        $this->integrationAudit('api_key.resumed', $r);
                        Notification::make()->title('Đã kích hoạt lại')->success()->send();
                    }),
                Action::make('revoke')
                    ->label('Thu hồi')->iconButton()->icon('heroicon-m-no-symbol')->color('danger')
                    ->visible(fn (IntegrationApiKey $r) => ! in_array($r->status, ['revoked', 'expired'], true))
                    ->requiresConfirmation()->modalDescription('Thu hồi vĩnh viễn — mọi request dùng key này sẽ bị từ chối.')
                    ->schema([TextInput::make('reason')->label('Lý do')->required()])
                    ->action(function (array $data, IntegrationApiKey $r): void {
                        $r->update(['status' => 'revoked']);
                        $this->integrationAudit('api_key.revoked', $r, reason: $data['reason']);
                        Notification::make()->title('Đã thu hồi API key')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có API key')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function detailAction(): Action
    {
        return Action::make('detail')
            ->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (IntegrationApiKey $r) => $r->name)
            ->modalContent(fn (IntegrationApiKey $r) => view('filament.pages.integration-api-key-detail', [
                'record' => $r->load(['scopes', 'rotations' => fn ($q) => $q->latest('rotated_at')->limit(5), 'owner']),
            ]))
            ->modalWidth('2xl')->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    private function rotate(IntegrationApiKey $r): void
    {
        $secret = app(IntegrationSecret::class);
        $plain = $secret->generateApiSecret();
        $old = $r->secret_hash;
        $new = $secret->hash($plain);
        $r->update(['secret_hash' => $new, 'metadata_json' => array_merge($r->metadata_json ?? [], ['secret_masked' => $secret->mask($plain)])]);
        IntegrationApiKeyRotation::create([
            'api_key_id' => $r->id, 'old_secret_hash' => $old, 'new_secret_hash' => $new,
            'rotated_by' => auth()->id(), 'rotated_at' => now(), 'reason' => 'manual rotation', 'created_at' => now(),
        ]);
        $this->integrationAudit('api_key.rotated', $r);
        Notification::make()->title('Secret mới (chỉ hiện MỘT LẦN)')->body($plain)->persistent()->success()->send();
    }
}
