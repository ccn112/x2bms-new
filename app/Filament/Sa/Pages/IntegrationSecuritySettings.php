<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesIntegrationAudit;
use App\Models\IntegrationApiKey;
use App\Models\IntegrationConnection;
use App\Models\IntegrationCredential;
use App\Models\IntegrationIpAllowlist;
use App\Models\IntegrationSecurityPolicy;
use App\Models\WebhookEndpoint;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * WEB-UX-28-10 — Integration Security Settings.
 *
 * Secret rotation, IP allowlist, HMAC enforcement, OAuth callback, rate limit, audit
 * retention, replay protection, emergency disable. Hành động nhạy cảm cần xác nhận +
 * quyền nâng cao (SuperAdmin). Mọi thay đổi ghi integration_audit_logs.
 */
class IntegrationSecuritySettings extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesIntegrationAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Integration Center';

    protected static ?string $navigationLabel = 'Bảo mật tích hợp';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Cài đặt bảo mật tích hợp';

    protected static ?string $slug = 'integrations/security';

    protected string $view = 'filament.pages.integration-security-settings';

    private function policy(string $key): array
    {
        return (array) (IntegrationSecurityPolicy::where('policy_key', $key)->value('policy_value_json') ?? []);
    }

    protected function getViewData(): array
    {
        return [
            'policies' => IntegrationSecurityPolicy::orderBy('policy_key')->get(),
            'rotation' => $this->policy('secret_rotation_policy'),
            'hmac' => $this->policy('hmac_signature_enforcement'),
            'rate' => $this->policy('rate_limiting_defaults'),
            'retention' => $this->policy('audit_retention_days'),
            'replay' => $this->policy('webhook_replay_protection'),
            'emergencyOn' => (bool) (IntegrationSecurityPolicy::where('policy_key', 'emergency_disable_switch')->value('is_enabled')
                && ($this->policy('emergency_disable_switch')['enabled'] ?? false)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Lưu cài đặt')->icon('heroicon-m-check')->color('primary')->modalWidth('xl')
                ->fillForm(fn () => [
                    'rotation_days' => $this->policy('secret_rotation_policy')['rotation_days'] ?? 90,
                    'audit_retention_days' => $this->policy('audit_retention_days')['days'] ?? 180,
                    'rate_limit_default' => $this->policy('rate_limiting_defaults')['default'] ?? 1000,
                    'replay_protection' => $this->policy('webhook_replay_protection')['enabled'] ?? true,
                ])
                ->schema([
                    TextInput::make('rotation_days')->label('Chu kỳ rotate secret (ngày)')->numeric()->required(),
                    TextInput::make('audit_retention_days')->label('Lưu audit (ngày)')->numeric()->required(),
                    TextInput::make('rate_limit_default')->label('Rate limit mặc định / phút')->numeric()->required(),
                    Toggle::make('replay_protection')->label('Chống replay webhook'),
                ])
                ->action(fn (array $data) => $this->saveSettings($data)),
            Action::make('enforceHmac')
                ->label('Bắt buộc HMAC')->icon('heroicon-m-lock-closed')->color('warning')
                ->requiresConfirmation()->modalDescription('Bật bắt buộc HMAC toàn hệ — endpoint chưa ký sẽ bị đánh dấu/chặn.')
                ->action(function (): void {
                    IntegrationSecurityPolicy::updateOrCreate(['policy_key' => 'hmac_signature_enforcement'],
                        ['policy_value_json' => array_merge($this->policy('hmac_signature_enforcement'), ['enforced' => true]), 'is_enabled' => true, 'updated_by' => auth()->id()]);
                    $flagged = WebhookEndpoint::where('signature_type', 'none')->count();
                    $this->integrationAudit('security.enforce_hmac', null, after: ['flagged_unsigned' => $flagged]);
                    Notification::make()->title("Đã bật bắt buộc HMAC ({$flagged} endpoint chưa ký bị gắn cờ)")->success()->send();
                }),
            Action::make('rotateExpiring')
                ->label('Rotate secret sắp hết hạn')->icon('heroicon-m-arrow-path')->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    $n = IntegrationCredential::where('status', 'expiring')->count();
                    IntegrationCredential::where('status', 'expiring')->update(['status' => 'rotated', 'rotated_at' => now(), 'rotated_by' => auth()->id()]);
                    $this->integrationAudit('security.rotate_expiring', null, after: ['rotated' => $n]);
                    Notification::make()->title("Đã đánh dấu rotate {$n} credential sắp hết hạn")->success()->send();
                }),
            Action::make('emergencyDisable')
                ->label('Tắt khẩn cấp')->icon('heroicon-m-exclamation-triangle')->color('danger')
                ->visible(fn () => auth()->user()?->isPlatformAdmin())
                ->requiresConfirmation()->modalIconColor('danger')
                ->modalHeading('Tắt khẩn cấp toàn bộ tích hợp')
                ->modalDescription('Tạm ngưng MỌI kết nối, API key và webhook. Chỉ SuperAdmin. Cần lý do.')
                ->schema([TextInput::make('reason')->label('Lý do')->required()])
                ->action(fn (array $data) => $this->emergencyDisable($data['reason'])),
        ];
    }

    private function saveSettings(array $data): void
    {
        foreach ([
            'secret_rotation_policy' => ['rotation_days' => (int) $data['rotation_days'], 'expiration_notice_days' => 7],
            'audit_retention_days' => ['days' => (int) $data['audit_retention_days']],
            'rate_limiting_defaults' => ['default' => (int) $data['rate_limit_default'], 'window' => '1 minute', 'burst' => 200],
            'webhook_replay_protection' => ['enabled' => (bool) ($data['replay_protection'] ?? false), 'nonce_ttl_minutes' => 10],
        ] as $key => $value) {
            IntegrationSecurityPolicy::updateOrCreate(['policy_key' => $key],
                ['policy_value_json' => $value, 'is_enabled' => true, 'updated_by' => auth()->id()]);
        }
        $this->integrationAudit('security.settings_updated', null, after: $data);
        Notification::make()->title('Đã lưu cài đặt bảo mật')->success()->send();
    }

    private function emergencyDisable(string $reason): void
    {
        IntegrationConnection::whereNot('status', 'disabled')->update(['status' => 'disabled']);
        IntegrationApiKey::where('status', 'active')->update(['status' => 'suspended']);
        WebhookEndpoint::whereNot('status', 'disabled')->update(['status' => 'disabled']);
        IntegrationSecurityPolicy::updateOrCreate(['policy_key' => 'emergency_disable_switch'],
            ['policy_value_json' => ['enabled' => true, 'at' => now()->toDateTimeString()], 'is_enabled' => true, 'updated_by' => auth()->id()]);
        $this->integrationAudit('security.emergency_disable', null, reason: $reason,
            after: ['connections_disabled' => true, 'api_keys_suspended' => true, 'webhooks_disabled' => true]);
        Notification::make()->title('ĐÃ TẮT KHẨN CẤP toàn bộ tích hợp')->danger()->persistent()->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(IntegrationIpAllowlist::query()->with('createdBy'))
            ->heading('IP Allowlist')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('ip_or_cidr')->label('IP / CIDR')->fontFamily('mono')->searchable(),
                TextColumn::make('scope_type')->label('Phạm vi')->badge()->color('gray'),
                TextColumn::make('description')->label('Mô tả')->toggleable(),
                TextColumn::make('createdBy.name')->label('Tạo bởi')->toggleable(),
                TextColumn::make('created_at')->label('Thời điểm')->since(),
            ])
            ->headerActions([
                Action::make('addIp')
                    ->label('Thêm IP')->icon('heroicon-m-plus')->color('primary')
                    ->schema([
                        TextInput::make('ip_or_cidr')->label('IP / CIDR')->required()->maxLength(64),
                        TextInput::make('description')->label('Mô tả')->maxLength(150),
                    ])
                    ->action(function (array $data): void {
                        $ip = IntegrationIpAllowlist::create($data + ['scope_type' => 'global', 'created_by' => auth()->id()]);
                        $this->integrationAudit('security.ip_allowlist_added', $ip, after: $data);
                        Notification::make()->title('Đã thêm IP vào allowlist')->success()->send();
                    }),
            ])
            ->recordActions([
                Action::make('remove')
                    ->iconButton()->icon('heroicon-m-trash')->color('danger')->requiresConfirmation()
                    ->action(function (IntegrationIpAllowlist $r): void {
                        $ip = $r->ip_or_cidr;
                        $r->delete();
                        $this->integrationAudit('security.ip_allowlist_removed', null, before: ['ip' => $ip]);
                        Notification::make()->title('Đã xoá IP khỏi allowlist')->success()->send();
                    }),
            ])
            ->paginated([10, 25]);
    }
}
