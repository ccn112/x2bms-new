<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email chứa mã OTP (đăng ký · đăng nhập · đặt lại mật khẩu · kích hoạt cư dân).
 *
 * 🚨 Trước bản này `OtpService::request()` chỉ cache mã rồi để lại một TODO —
 * KHÔNG gửi đi đâu cả. Ở dev còn trả `dev_code` nên vẫn test được, nhưng lên
 * production `dev_code` là null ⇒ toàn bộ luồng đăng ký / đăng nhập OTP / đặt
 * lại mật khẩu sẽ chết. Đây là mailable bù chỗ đó.
 */
class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $purpose,
        public readonly int $ttlSeconds,
    ) {}

    /** Tiêu đề nói rõ việc gì để người nhận không tưởng là thư lạ. */
    public function envelope(): Envelope
    {
        $subject = match ($this->purpose) {
            'register' => 'Mã xác thực đăng ký X2-BMS',
            'login' => 'Mã đăng nhập X2-BMS',
            'password_reset' => 'Mã đặt lại mật khẩu X2-BMS',
            'resident_activation' => 'Mã kích hoạt cư dân X2-BMS',
            default => 'Mã xác thực X2-BMS',
        };

        return new Envelope(subject: $subject.' — '.$this->code);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp_code',
            with: [
                'code' => $this->code,
                'purpose' => $this->purpose,
                'minutes' => (int) ceil($this->ttlSeconds / 60),
            ],
        );
    }
}
