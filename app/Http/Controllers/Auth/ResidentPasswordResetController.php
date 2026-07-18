<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * Trang đặt lại mật khẩu (guest) tiêu thụ token do BQL sinh ở màn cư dân
 * (xem App\Filament\Concerns\ResetsResidentPassword). Dùng Password broker chuẩn.
 */
class ResidentPasswordResetController extends Controller
{
    public function show(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [], ['password' => 'mật khẩu']);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // cast 'hashed' trên User tự băm khi lưu.
                $user->forceFill(['password' => $password])->save();
            }
        );

        if ($status === Password::PasswordReset) {
            return view('auth.reset-password', ['done' => true]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
