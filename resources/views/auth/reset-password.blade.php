<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đặt lại mật khẩu · X2-BMS</title>
    <style>
        :root { --navy:#0b1b3f; --navy2:#122a5c; --gold:#c8a24c; --slate:#64748b; --line:#e2e8f0; --red:#dc2626; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Inter',system-ui,-apple-system,'Segoe UI',sans-serif; background:linear-gradient(135deg,var(--navy),var(--navy2)); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; color:#0f172a; }
        .card { width:100%; max-width:420px; background:#fff; border-radius:20px; box-shadow:0 24px 60px -20px rgba(0,0,0,.5); overflow:hidden; }
        .head { background:var(--navy); padding:28px 28px 22px; text-align:center; }
        .brand { color:#fff; font-weight:800; font-size:22px; letter-spacing:.5px; }
        .brand span { color:var(--gold); }
        .sub { color:#94a3b8; font-size:12px; margin-top:4px; }
        .body { padding:26px 28px 30px; }
        h1 { font-size:18px; font-weight:700; color:var(--navy); margin-bottom:6px; }
        p.lead { font-size:13px; color:var(--slate); margin-bottom:20px; line-height:1.5; }
        label { display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:6px; }
        .field { margin-bottom:16px; }
        input[type=email],input[type=password] { width:100%; height:44px; border:1px solid var(--line); border-radius:10px; padding:0 13px; font-size:14px; color:#0f172a; outline:none; transition:border .15s; }
        input:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(200,162,76,.15); }
        input[readonly] { background:#f8fafc; color:#64748b; }
        .btn { width:100%; height:46px; border:none; border-radius:10px; background:var(--navy); color:#fff; font-size:14px; font-weight:700; cursor:pointer; transition:opacity .15s; margin-top:4px; }
        .btn:hover { opacity:.92; }
        .err { background:#fef2f2; border:1px solid #fecaca; color:var(--red); font-size:12.5px; border-radius:10px; padding:10px 12px; margin-bottom:16px; }
        .err ul { margin:0; padding-left:16px; }
        .hint { font-size:11.5px; color:#94a3b8; margin-top:7px; }
        .done { text-align:center; padding:8px 0 4px; }
        .done .ic { width:60px; height:60px; border-radius:50%; background:#dcfce7; color:#16a34a; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; }
        .link { display:inline-block; margin-top:18px; color:var(--navy); font-size:13px; font-weight:600; text-decoration:none; border-bottom:1px solid var(--gold); padding-bottom:1px; }
        .foot { text-align:center; font-size:11px; color:#94a3b8; padding:14px; border-top:1px solid var(--line); }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            <div class="brand">X2<span>-BMS</span></div>
            <div class="sub">Hệ thống quản lý tòa nhà</div>
        </div>
        <div class="body">
            @if (! empty($done))
                <div class="done">
                    <div class="ic">
                        <svg width="30" height="30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </div>
                    <h1>Đặt lại mật khẩu thành công</h1>
                    <p class="lead">Bạn có thể đăng nhập bằng mật khẩu mới ngay bây giờ.</p>
                    <a class="link" href="{{ url('/admin') }}">Đến trang đăng nhập →</a>
                </div>
            @else
                <h1>Đặt lại mật khẩu</h1>
                <p class="lead">Nhập mật khẩu mới cho tài khoản cư dân của bạn.</p>

                @if ($errors->any())
                    <div class="err">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.store') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $email ?? '') }}" readonly>
                    </div>
                    <div class="field">
                        <label for="password">Mật khẩu mới</label>
                        <input id="password" type="password" name="password" autocomplete="new-password" required autofocus>
                        <div class="hint">Tối thiểu 8 ký tự.</div>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Nhập lại mật khẩu</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                    </div>
                    <button class="btn" type="submit">Đặt lại mật khẩu</button>
                </form>
            @endif
        </div>
        <div class="foot">© {{ date('Y') }} X2-BMS · Tanadaithanh</div>
    </div>
</body>
</html>
