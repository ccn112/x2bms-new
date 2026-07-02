<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * WEB-UX-02B — Bảo mật & 2FA (Security).
 * Password change (real), two-factor toggle + recovery codes, and login-alert
 * preferences. Reached from the header avatar menu. Sensitive actions write audit.
 */
class SecuritySettings extends Page
{
    protected static ?string $slug = 'security';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Bảo mật & 2FA';

    protected string $view = 'filament.pages.security-settings';

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public bool $twoFa = false;

    public bool $loginAlert = true;

    public bool $failedLoginAlert = true;

    public int $sessionTimeout = 30;

    public function mount(): void
    {
        $this->twoFa = (bool) session('x2_2fa_enabled', false);
        $this->loginAlert = (bool) session('x2_login_alert', true);
        $this->failedLoginAlert = (bool) session('x2_failed_login_alert', true);
        $this->sessionTimeout = (int) session('x2_session_timeout', 30);
    }

    public function changePassword(): void
    {
        Validator::make(
            ['current_password' => $this->current_password, 'new_password' => $this->new_password, 'new_password_confirmation' => $this->new_password_confirmation],
            [
                'current_password' => ['required'],
                'new_password' => ['required', 'string', 'min:8', 'confirmed'],
            ],
            [],
            ['current_password' => 'Mật khẩu hiện tại', 'new_password' => 'Mật khẩu mới']
        )->validate();

        $u = auth()->user();
        if (! Hash::check($this->current_password, $u->password)) {
            $this->addError('current_password', 'Mật khẩu hiện tại không đúng.');

            return;
        }

        $u->update(['password' => Hash::make($this->new_password)]);
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->audit('security.password_change', 'Đổi mật khẩu');
        Notification::make()->title('Đã đổi mật khẩu')->success()->send();
    }

    public function toggle2fa(): void
    {
        $this->twoFa = ! $this->twoFa;
        session(['x2_2fa_enabled' => $this->twoFa]);
        $this->audit('security.2fa_'.($this->twoFa ? 'enable' : 'disable'), ($this->twoFa ? 'Bật' : 'Tắt').' xác thực 2 lớp');
        Notification::make()->title($this->twoFa ? 'Đã bật 2FA' : 'Đã tắt 2FA')->{$this->twoFa ? 'success' : 'warning'}()->send();
    }

    public function saveAlerts(): void
    {
        session([
            'x2_login_alert' => $this->loginAlert,
            'x2_failed_login_alert' => $this->failedLoginAlert,
            'x2_session_timeout' => $this->sessionTimeout,
        ]);
        $this->audit('security.alerts_update', 'Cập nhật cảnh báo đăng nhập');
        Notification::make()->title('Đã lưu cấu hình cảnh báo')->success()->send();
    }

    /** @return array<int,string> */
    public function recoveryCodes(): array
    {
        return collect(range(1, 8))->map(fn ($i) => strtoupper(Str::random(4).'-'.Str::random(4)))->all();
    }

    private function audit(string $action, string $desc): void
    {
        $u = auth()->user();
        AuditLog::create([
            'tenant_id' => $u->tenant_id, 'building_id' => $u->building_id,
            'user_id' => $u->id, 'actor_name' => $u->name, 'action' => $action, 'description' => $desc,
        ]);
    }
}
