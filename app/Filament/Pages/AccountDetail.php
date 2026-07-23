<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\GlobalUserAccount;
use App\Models\MobileDevice;
use App\Models\ResidentUnitBinding;
use App\Models\User;
use App\Support\Context\CurrentContext;
use App\Support\Rules\AccountActivationRules;
use App\Support\Rules\RiskLevel;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * BQL-02-06 — Chi tiết tài khoản & quyền (Account Detail).
 * Xem 1 global_user_account: định danh, căn đã gắn (resident_unit_bindings), thiết bị
 * (MobileDevice), cảnh báo rule (Module 0) + mời kích hoạt / khóa-mở. Vào từ màn 05.
 */
class AccountDetail extends Page
{
    protected static ?string $slug = 'resident-accounts/{record}/detail';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.account-detail';

    public GlobalUserAccount $record;

    public function mount(GlobalUserAccount $record): void
    {
        // Chỉ cho xem tài khoản gắn căn trong tòa của BQL.
        $inScope = ResidentUnitBinding::query()
            ->where('user_account_id', $record->id)
            ->whereIn('building_id', app(CurrentContext::class)->buildingIds() ?: [0])
            ->exists();
        if (! $inScope) {
            throw new NotFoundHttpException;
        }
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return 'Tài khoản · '.($this->record->full_name ?: $this->record->phone ?: $this->record->email ?: ('#'.$this->record->id));
    }

    public function invite(): void
    {
        $meta = $this->record->metadata_json ?? [];
        $meta['activation_invited_at'] = now()->toIso8601String();
        $this->record->update(['metadata_json' => $meta]);
        $this->audit('account.activation.invite', 'Gửi lời mời kích hoạt');
        Notification::make()->title('Đã gửi lời mời kích hoạt')->success()->send();
    }

    public function lock(): void
    {
        $this->record->update(['account_status' => 'suspended']);
        $this->audit('account.lock', 'Khóa tài khoản');
        Notification::make()->title('Đã khóa tài khoản')->warning()->send();
    }

    public function unlock(): void
    {
        $this->record->update(['account_status' => 'active']);
        $this->audit('account.unlock', 'Mở khóa tài khoản');
        Notification::make()->title('Đã mở khóa tài khoản')->success()->send();
    }

    private function audit(string $action, string $description): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id, 'building_id' => $user->building_id,
            'user_id' => $user->id, 'actor_name' => $user->name,
            'action' => $action, 'subject_type' => GlobalUserAccount::class, 'subject_id' => $this->record->id,
            'description' => $description.' · '.($this->record->full_name ?: '#'.$this->record->id),
        ]);
    }

    protected function getViewData(): array
    {
        $a = $this->record;

        $bindings = ResidentUnitBinding::query()
            ->where('user_account_id', $a->id)
            ->with('apartment.building')
            ->get()
            ->map(fn (ResidentUnitBinding $b) => [
                'apartment' => $b->apartment?->code ?? '—',
                'building' => $b->apartment?->building?->name,
                'role' => match ($b->role) { 'owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'family_member' => 'Thành viên', 'guest' => 'Khách', default => $b->role },
                'status' => $b->status,
                'starts_at' => $b->starts_at,
            ])->all();

        // Thiết bị (match user theo email — heuristic v1, như màn 05).
        $devices = [];
        if (filled($a->email)) {
            $userIds = User::query()->where('email', $a->email)->pluck('id');
            if ($userIds->isNotEmpty()) {
                $devices = MobileDevice::query()->whereIn('user_id', $userIds)
                    ->orderByDesc('last_seen_at')->get()
                    ->map(fn (MobileDevice $d) => [
                        'platform' => $d->platform,
                        'app_version' => $d->app_version,
                        'last_seen_at' => $d->last_seen_at,
                        'revoked' => $d->revoked_at !== null,
                    ])->all();
            }
        }

        $activeDevices = collect($devices)->where('revoked', false)->count();
        $report = AccountActivationRules::forAccount($a, $activeDevices);

        return [
            'a' => $a,
            'bindings' => $bindings,
            'devices' => $devices,
            'risk' => array_map(fn (array $f) => [
                'label' => $f['message'], 'tone' => RiskLevel::tone($f['level']),
                'checklist' => $f['checklist'],
            ], $report->toArray()),
        ];
    }
}
