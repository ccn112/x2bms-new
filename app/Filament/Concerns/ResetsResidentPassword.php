<?php

namespace App\Filament\Concerns;

use App\Models\AuditLog;
use App\Models\Resident;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Luồng "Đặt lại mật khẩu cư dân" dùng chung cho màn Danh sách + Chi tiết cư dân.
 * 4 phương thức: mật khẩu tạm (hiện 1 lần) · gửi OTP · gửi link đặt lại · tạo link
 * để copy (gửi Zalo). Sau khi tạo, mở modal kết quả có ô copy sẵn.
 */
trait ResetsResidentPassword
{
    private const RESET_METHODS = [
        'temp' => 'Cấp mật khẩu tạm (hiện 1 lần)',
        'otp' => 'Gửi mã OTP đăng nhập',
        'link_send' => 'Gửi link đặt lại mật khẩu',
        'link_copy' => 'Tạo link để copy (gửi qua Zalo)',
    ];

    private const RESET_CHANNELS = ['sms' => 'SMS', 'zalo' => 'Zalo', 'email' => 'Email'];

    /** Schema popup chọn phương thức đặt lại. */
    protected function resetPasswordSchema(): array
    {
        return [
            Radio::make('method')
                ->label('Phương thức')
                ->options(self::RESET_METHODS)
                ->descriptions([
                    'temp' => 'Sinh mật khẩu ngẫu nhiên, hiển thị một lần cho BQL đọc lại cho cư dân.',
                    'otp' => 'Sinh mã OTP 6 số (hiệu lực 10 phút) gửi qua kênh đã chọn.',
                    'link_send' => 'Gửi link đặt lại mật khẩu tới cư dân qua kênh đã chọn.',
                    'link_copy' => 'Tạo link đặt lại để BQL tự copy gửi cư dân (ví dụ dán vào Zalo).',
                ])
                ->default('temp')
                ->required()
                ->live(),
            Select::make('channel')
                ->label('Kênh gửi')
                ->options(self::RESET_CHANNELS)
                ->default('zalo')
                ->required()
                ->visible(fn (Get $get): bool => in_array($get('method'), ['otp', 'link_send'], true)),
            Textarea::make('note')
                ->label('Ghi chú (nội bộ)')
                ->rows(2),
        ];
    }

    /**
     * Xử lý đặt lại mật khẩu → sinh dữ liệu → mở modal kết quả (có ô copy).
     * Gọi từ ->action() của cả row-action (list) lẫn header-action (detail).
     */
    protected function handleResidentPasswordReset(Resident $resident, array $data): void
    {
        $method = $data['method'] ?? 'temp';
        $channel = $data['channel'] ?? null;
        $user = $resident->linkedUser;
        $phone = $resident->phone ?? $resident->contact_phone;

        // Đặt lại mật khẩu chỉ có nghĩa khi cư dân đã có tài khoản đăng nhập liên kết.
        if (! $user) {
            Notification::make()->title('Cư dân chưa có tài khoản đăng nhập')
                ->body('Chưa thể đặt lại mật khẩu. Hãy kích hoạt/liên kết tài khoản cho cư dân trước.')
                ->warning()->send();

            return;
        }
        $email = $user->email;

        $args = ['method' => $method, 'channel' => $channel, 'name' => $resident->full_name];

        switch ($method) {
            case 'temp':
                $pwd = Str::password(10, symbols: false); // hiển thị 1 lần; cast 'hashed' tự băm khi lưu
                $user->update(['password' => $pwd]);
                $args += ['kind' => 'password', 'value' => $pwd];
                break;

            case 'otp':
                $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                Cache::put('resident_pwd_otp_'.$resident->id, $otp, now()->addMinutes(10));
                $sentTo = $this->deliverResidentMail($channel, $email, 'Mã OTP đăng nhập X2-BMS', $this->otpEmailHtml($resident, $otp));
                $args += ['kind' => 'otp', 'value' => $otp, 'sent' => (bool) $sentTo, 'target' => $sentTo ?? ($channel === 'email' ? $email : $phone), 'testMode' => filled(config('mail.test_to'))];
                break;

            case 'link_send':
            case 'link_copy':
                $url = $this->makeResetLink($user);
                $sentTo = $method === 'link_send'
                    ? $this->deliverResidentMail($channel, $email, 'Đặt lại mật khẩu X2-BMS', $this->resetLinkEmailHtml($resident, $url))
                    : null;
                $args += ['kind' => 'link', 'value' => $url, 'sent' => (bool) $sentTo, 'target' => $sentTo ?? ($channel === 'email' ? $email : $phone), 'testMode' => filled(config('mail.test_to'))];
                break;
        }

        $this->audit(
            'resident.password_reset',
            'Đặt lại mật khẩu ('.self::RESET_METHODS[$method].($channel ? ' · '.self::RESET_CHANNELS[$channel] : '').') cho '.$resident->full_name
        );

        $this->replaceMountedAction('residentResetResult', $args);
    }

    /** Sinh token đặt lại qua Password broker chuẩn Laravel (lưu password_reset_tokens), trả URL. */
    private function makeResetLink(User $user): string
    {
        $token = Password::broker()->createToken($user);
        $base = rtrim((string) config('app.url'), '/');

        return $base.'/reset-password/'.$token.'?email='.urlencode($user->email);
    }

    /**
     * Gửi email nghiệp vụ. Nếu đặt mail.test_to (chế độ test) → gửi tới địa chỉ test
     * cho mọi kênh (để kiểm thử OTP/link). Ngược lại chỉ gửi khi kênh = email
     * (SMS/Zalo chưa nối gateway). Trả địa chỉ đã gửi, hoặc null nếu không gửi.
     */
    private function deliverResidentMail(?string $channel, ?string $realEmail, string $subject, string $html): ?string
    {
        $testTo = config('mail.test_to');
        if ($channel !== 'email' && blank($testTo)) {
            return null; // SMS/Zalo: chưa có gateway, không gửi
        }
        $to = $testTo ?: $realEmail;
        if (blank($to)) {
            return null;
        }
        try {
            Mail::html($html, fn ($m) => $m->to($to)->subject($subject));

            return $to;
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title('Không gửi được email')->body($e->getMessage())->danger()->send();

            return null;
        }
    }

    private function otpEmailHtml(Resident $r, string $otp): string
    {
        return $this->mailShell('Mã OTP đăng nhập',
            '<p>Xin chào '.e($r->full_name).',</p>'
            .'<p>Mã OTP đăng nhập tài khoản cư dân của bạn là:</p>'
            .'<p style="font-size:30px;font-weight:800;letter-spacing:6px;color:#0b2146;margin:16px 0;">'.e($otp).'</p>'
            .'<p style="color:#64748b;font-size:13px;">Mã có hiệu lực trong 10 phút. Vui lòng không chia sẻ mã này cho bất kỳ ai.</p>');
    }

    private function resetLinkEmailHtml(Resident $r, string $url): string
    {
        return $this->mailShell('Đặt lại mật khẩu',
            '<p>Xin chào '.e($r->full_name).',</p>'
            .'<p>Nhấn nút bên dưới để đặt lại mật khẩu tài khoản cư dân của bạn:</p>'
            .'<p style="margin:20px 0;"><a href="'.e($url).'" style="background:#0b2146;color:#fff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:700;display:inline-block;">Đặt lại mật khẩu</a></p>'
            .'<p style="color:#64748b;font-size:12px;word-break:break-all;">Hoặc mở link: '.e($url).'</p>'
            .'<p style="color:#64748b;font-size:13px;">Link hết hạn sau 60 phút.</p>');
    }

    private function mailShell(string $title, string $body): string
    {
        return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:0 auto;">'
            .'<div style="background:#0b2146;padding:20px;text-align:center;border-radius:12px 12px 0 0;">'
            .'<span style="color:#fff;font-size:20px;font-weight:800;">X2<span style="color:#d5a331;">-BMS</span></span></div>'
            .'<div style="border:1px solid #e2e8f0;border-top:none;padding:24px;border-radius:0 0 12px 12px;color:#0f172a;font-size:14px;line-height:1.6;">'
            .'<h2 style="margin:0 0 12px;font-size:17px;color:#0b2146;">'.e($title).'</h2>'.$body
            .'<p style="margin-top:20px;color:#94a3b8;font-size:12px;">Email tự động từ hệ thống X2-BMS.</p>'
            .'</div></div>';
    }

    /** Modal kết quả (auto-discover qua tên *Action) — hiện giá trị + nút copy. */
    public function residentResetResultAction(): Action
    {
        return Action::make('residentResetResult')
            ->modalHeading('Kết quả đặt lại mật khẩu')
            ->modalIcon('heroicon-o-key')
            ->modalWidth('md')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Đóng')
            ->modalContent(fn (array $arguments): HtmlString => $this->resetResultContent($arguments));
    }

    private function resetResultContent(array $a): HtmlString
    {
        $kind = $a['kind'] ?? 'link';
        $value = $a['value'] ?? '';
        $sent = $a['sent'] ?? false;
        $target = $a['target'] ?? null;
        $testMode = $a['testMode'] ?? false;

        $sentNote = $sent
            ? 'Đã gửi qua email tới <b>'.e((string) $target).'</b>'.($testMode ? ' <span class="text-amber-600">(chế độ test)</span>' : '').'.'
            : '';

        $heading = match ($kind) {
            'password' => 'Mật khẩu tạm cho '.($a['name'] ?? 'cư dân'),
            'otp' => 'Mã OTP đăng nhập',
            default => 'Link đặt lại mật khẩu',
        };
        $hint = match ($kind) {
            'password' => 'Đọc lại mật khẩu này cho cư dân. Mật khẩu chỉ hiển thị <b>một lần</b> — hãy sao chép ngay.',
            'otp' => 'OTP hiệu lực <b>10 phút</b>. '.$sentNote,
            default => $sent
                ? $sentNote.' Bạn cũng có thể copy để gửi thêm.'
                : 'Copy link dưới đây và gửi cho cư dân (ví dụ dán vào Zalo).',
        };

        $inputId = 'x2-reset-val';
        $mono = $kind === 'link' ? 'text-xs' : 'font-title text-lg font-bold tracking-wide';

        return new HtmlString(
            '<div class="space-y-3" x-data="{ copied: false }">'
            .'<p class="text-sm text-slate-600">'.$hint.'</p>'
            .'<div class="flex items-stretch gap-2">'
            .'<input id="'.$inputId.'" type="text" readonly value="'.e($value).'" '
            .'class="'.$mono.' w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-slate-800 focus:border-x2-primary focus:ring-0" '
            .'onclick="this.select()" />'
            .'<button type="button" '
            ."x-on:click=\"navigator.clipboard.writeText('".e(addslashes($value))."'); copied = true; setTimeout(() => copied = false, 1500)\" "
            .'class="shrink-0 rounded-lg bg-x2-primary px-3 py-2 text-sm font-semibold text-white hover:opacity-90">'
            .'<span x-show="! copied">Copy</span><span x-show="copied" x-cloak>Đã copy ✓</span>'
            .'</button></div>'
            .'<p class="text-[11px] text-slate-400">'.e($heading).'</p>'
            .'</div>'
        );
    }
}
