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
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

/**
 * BQL-02-05 — Hàng đợi kích hoạt tài khoản (Account Activation Queue).
 * Tài khoản gốc (global_user_accounts) đã gắn căn trong tòa của BQL: mời/gửi lại lời mời
 * kích hoạt, khóa/mở. Rule (Module 0) cảnh báo trùng danh tính / chưa xác thực / thiếu thiết bị.
 * Activation dựa GlobalUserAccount + track thiết bị (MobileDevice) — theo quyết định dự án.
 */
class AccountActivationQueue extends Page
{
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationLabel = 'Kích hoạt tài khoản';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'Hàng đợi kích hoạt tài khoản';

    protected static ?string $slug = 'resident-accounts/activations';

    protected string $view = 'filament.pages.account-activation-queue';

    public string $search = '';

    public string $statusFilter = 'all'; // all|unverified|verified|suspended

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /** @return array<int> */
    private function buildingIds(): array
    {
        return app(CurrentContext::class)->buildingIds() ?: [0];
    }

    /** Global accounts gắn căn trong tòa của BQL. */
    private function scoped(): Builder
    {
        $accountIds = ResidentUnitBinding::query()
            ->whereIn('building_id', $this->buildingIds())
            ->pluck('user_account_id')->unique();

        return GlobalUserAccount::query()->whereIn('id', $accountIds);
    }

    public function invite(int $id): void
    {
        $account = $this->scopedFind($id);
        if (! $account) {
            return;
        }
        // Gửi lời mời kích hoạt (link/OTP) — hạ tầng SMS/Zalo/email nối sau; ghi audit + đánh dấu.
        $meta = $account->metadata_json ?? [];
        $meta['activation_invited_at'] = now()->toIso8601String();
        $account->update(['metadata_json' => $meta]);

        $this->audit('account.activation.invite', 'Gửi lời mời kích hoạt: '.$this->label($account));
        Notification::make()->title('Đã gửi lời mời kích hoạt')->success()->send();
    }

    public function lock(int $id): void
    {
        $account = $this->scopedFind($id);
        if (! $account) {
            return;
        }
        $account->update(['account_status' => 'suspended']);
        $this->audit('account.lock', 'Khóa tài khoản: '.$this->label($account));
        Notification::make()->title('Đã khóa tài khoản')->warning()->send();
    }

    public function unlock(int $id): void
    {
        $account = $this->scopedFind($id);
        if (! $account) {
            return;
        }
        $account->update(['account_status' => 'active']);
        $this->audit('account.unlock', 'Mở khóa tài khoản: '.$this->label($account));
        Notification::make()->title('Đã mở khóa tài khoản')->success()->send();
    }

    private function scopedFind(int $id): ?GlobalUserAccount
    {
        return $this->scoped()->whereKey($id)->first();
    }

    private function label(GlobalUserAccount $a): string
    {
        return ($a->full_name ?: $a->phone ?: $a->email ?: ('#'.$a->id));
    }

    /** Số thiết bị đang hoạt động của account (match user theo email — heuristic v1). */
    private function deviceCountFor(GlobalUserAccount $a): int
    {
        if (blank($a->email)) {
            return 0;
        }
        $userIds = User::query()->where('email', $a->email)->pluck('id');
        if ($userIds->isEmpty()) {
            return 0;
        }

        return MobileDevice::query()->whereIn('user_id', $userIds)->whereNull('revoked_at')->count();
    }

    private function audit(string $action, string $description): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id, 'building_id' => $user->building_id,
            'user_id' => $user->id, 'actor_name' => $user->name,
            'action' => $action, 'description' => $description,
        ]);
    }

    protected function getViewData(): array
    {
        $base = $this->scoped();

        $kpis = [
            ['label' => 'Tổng tài khoản', 'value' => (clone $base)->count(), 'accent' => 'blue'],
            ['label' => 'Chưa xác thực', 'value' => (clone $base)->where('identity_status', '!=', 'verified')->count(), 'accent' => 'amber'],
            ['label' => 'Nghi trùng', 'value' => (clone $base)->whereNotNull('duplicate_group_id')->count(), 'accent' => 'red'],
            ['label' => 'Đang khóa', 'value' => (clone $base)->where('account_status', 'suspended')->count(), 'accent' => 'red'],
        ];

        $page = (clone $base)
            ->when($this->search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('full_name', 'like', '%'.$this->search.'%')
                ->orWhere('phone', 'like', '%'.$this->search.'%')
                ->orWhere('email', 'like', '%'.$this->search.'%')))
            ->when($this->statusFilter === 'unverified', fn (Builder $q) => $q->where('identity_status', '!=', 'verified'))
            ->when($this->statusFilter === 'verified', fn (Builder $q) => $q->where('identity_status', 'verified'))
            ->when($this->statusFilter === 'suspended', fn (Builder $q) => $q->where('account_status', 'suspended'))
            ->orderByDesc('risk_score')->orderByDesc('id')
            ->paginate(20);

        $rows = $page->getCollection()->map(function (GlobalUserAccount $a) {
            $devices = $this->deviceCountFor($a);
            $report = AccountActivationRules::forAccount($a, $devices);

            return [
                'id' => $a->id,
                'name' => $a->full_name ?: '—',
                'phone' => $a->phone,
                'email' => $a->email,
                'identity_status' => $a->identity_status,
                'account_status' => $a->account_status,
                'devices' => $devices,
                'invited' => filled(($a->metadata_json ?? [])['activation_invited_at'] ?? null),
                'risks' => array_map(fn (array $f) => [
                    'label' => $f['message'], 'tone' => RiskLevel::tone($f['level']),
                ], $report->toArray()),
            ];
        })->all();

        return ['kpis' => $kpis, 'rows' => $rows, 'page' => $page];
    }
}
