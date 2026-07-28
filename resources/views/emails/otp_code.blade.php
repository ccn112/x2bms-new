@php
    $label = match ($purpose) {
        'register' => 'đăng ký tài khoản',
        'login' => 'đăng nhập',
        'password_reset' => 'đặt lại mật khẩu',
        'resident_activation' => 'kích hoạt cư dân',
        default => 'xác thực',
    };
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Mã xác thực X2-BMS</title>
</head>
<body style="margin:0;padding:24px;background:#F5F8FD;font-family:Arial,Helvetica,sans-serif;color:#102A5C">
<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:16px;padding:28px">
    <div style="font-size:22px;font-weight:800;letter-spacing:-0.5px;color:#0B51F0">X2<span style="color:#102A5C">-BMS</span></div>

    <p style="font-size:15px;line-height:22px;margin:20px 0 8px">
        Mã {{ $label }} của bạn:
    </p>

    {{-- Mã để dạng khối lớn, dễ đọc/copy trên điện thoại. --}}
    <div style="font-size:34px;font-weight:800;letter-spacing:8px;color:#0B51F0;background:#EEF4FF;border-radius:12px;padding:16px;text-align:center;margin:8px 0 16px">
        {{ $code }}
    </div>

    <p style="font-size:14px;line-height:20px;color:#62749B;margin:0 0 6px">
        Mã có hiệu lực trong <strong>{{ $minutes }} phút</strong> và chỉ dùng được một lần.
    </p>
    <p style="font-size:14px;line-height:20px;color:#62749B;margin:0">
        Không chia sẻ mã cho bất kỳ ai, kể cả nhân viên ban quản lý. Nếu bạn không yêu cầu mã này,
        hãy bỏ qua email và đổi mật khẩu cho chắc chắn.
    </p>

    <hr style="border:none;border-top:1px solid #DCE6F7;margin:22px 0">
    <p style="font-size:12px;line-height:18px;color:#8A98B5;margin:0">
        Email tự động từ hệ thống X2-BMS. Vui lòng không trả lời email này.
    </p>
</div>
</body>
</html>
