<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Auth\TokenService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function __construct(private readonly TokenService $tokens) {}

    /** POST /api/v1/auth/login — identifier (phone|email) + password → token pair. */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $deviceId = $this->deviceId($request);

        $user = User::query()
            ->where('email', $data['identifier'])
            ->orWhere('phone', $data['identifier'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return ApiResponse::error('AUTH_INVALID_CREDENTIALS', __('Thông tin đăng nhập không đúng.'), 401);
        }

        $pair = $this->tokens->issuePair($user, $deviceId);

        return ApiResponse::success([
            'tokens' => $pair,
            'user' => $this->publicUser($user),
        ]);
    }

    /**
     * POST /api/v1/auth/register — tạo tài khoản public_user (đăng ký từ app).
     * Luồng A (chốt 2026-07-21): email + mật khẩu + OTP xác thực email.
     * KHÔNG tạo resident; việc gắn cư dân vào dự án đi qua duyệt/kích hoạt (Slice 1).
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'code' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            // Dự án quan tâm chọn ở màn đăng ký (mã `public_projects.code`).
            // Không bắt buộc và KHÔNG cấp quyền gì — xem User::interestedProjects.
            'project_codes' => ['sometimes', 'array', 'max:10'],
            'project_codes.*' => ['string', 'max:64'],
        ]);

        // Xác thực OTP đã gửi qua otp/request(purpose=register) tới email.
        $check = app(OtpService::class)->verify('email', $data['email'], 'register', $data['code']);
        if (! $check['valid']) {
            return ApiResponse::error(
                'OTP_'.strtoupper($check['reason'] ?? 'INVALID'),
                __('Mã OTP không hợp lệ.'),
                422,
                retryable: ($check['reason'] ?? '') === 'mismatch',
            );
        }

        if (User::where('email', $data['email'])->exists()) {
            return ApiResponse::error('AUTH_EMAIL_TAKEN', __('Email đã được đăng ký.'), 422);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'], // cast 'hashed' tự băm
            'account_type' => 'public_user',
        ]);

        // Mã lạ/không còn public thì BỎ QUA lặng lẽ chứ không chặn đăng ký:
        // danh mục dự án thay đổi liên tục, không đáng để hỏng cả luồng tạo
        // tài khoản chỉ vì một mã cũ.
        $projectIds = collect($data['project_codes'] ?? [])
            ->filter()
            ->unique()
            ->pipe(fn ($codes) => $codes->isEmpty()
                ? collect()
                : \App\Models\PublicProject::query()
                    ->where('is_public', true)
                    ->whereIn('code', $codes->all())
                    ->pluck('id'));

        if ($projectIds->isNotEmpty()) {
            $user->interestedProjects()->syncWithoutDetaching(
                $projectIds->mapWithKeys(fn ($id) => [$id => ['source' => 'register']])->all()
            );
        }

        return ApiResponse::success([
            'tokens' => $this->tokens->issuePair($user, $this->deviceId($request)),
            'user' => $this->publicUser($user),
            'interested_project_count' => $projectIds->count(),
        ], status: 201);
    }

    /**
     * POST /api/v1/auth/password/forgot — gửi mã OTP đặt lại mật khẩu qua EMAIL.
     *
     * KHÔNG tiết lộ email có tồn tại hay không: luôn trả 200 với cùng một thông
     * điệp. Nói "email không tồn tại" là biến endpoint này thành công cụ dò danh
     * sách người dùng.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        $exists = User::where('email', $data['email'])->exists();
        $result = ['sent' => false, 'expires_in' => 0, 'dev_code' => null];

        if ($exists) {
            $result = app(OtpService::class)->request('email', $data['email'], 'password_reset');
        }

        return ApiResponse::success([
            // `sent` chỉ phản ánh việc đã gửi được email hay chưa khi email tồn
            // tại; app không dùng nó để suy ra tài khoản có tồn tại.
            'sent' => true,
            'expires_in' => $result['expires_in'] ?: config('mobile.otp.ttl_seconds'),
            'message' => 'Nếu email đã đăng ký, mã đặt lại mật khẩu sẽ được gửi tới hộp thư.',
        ] + ($result['dev_code'] !== null ? ['dev_code' => $result['dev_code']] : []));
    }

    /**
     * POST /api/v1/auth/password/reset — đổi mật khẩu bằng mã OTP.
     *
     * Đổi mật khẩu xong **thu hồi toàn bộ token cũ** rồi cấp cặp token mới: nếu
     * ai đó đang đăng nhập bằng mật khẩu cũ (kịch bản mất tài khoản) thì họ phải
     * bị đẩy ra ngay, đó chính là mục đích của việc đặt lại mật khẩu.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'code' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $check = app(OtpService::class)->verify('email', $data['email'], 'password_reset', $data['code']);
        if (! $check['valid']) {
            return ApiResponse::error(
                'OTP_'.strtoupper($check['reason'] ?? 'INVALID'),
                __('Mã OTP không hợp lệ.'),
                422,
                retryable: ($check['reason'] ?? '') === 'mismatch',
            );
        }

        $user = User::where('email', $data['email'])->first();
        if (! $user) {
            return ApiResponse::error('AUTH_USER_NOT_FOUND', __('Không tìm thấy tài khoản.'), 404);
        }

        $user->forceFill(['password' => $data['password']])->save(); // cast 'hashed'
        $user->tokens()->delete();

        return ApiResponse::success([
            'tokens' => $this->tokens->issuePair($user, $this->deviceId($request)),
            'user' => $this->publicUser($user),
        ]);
    }

    /** POST /api/v1/auth/refresh — Bearer <refresh_token> with ability token:refresh → new pair. */
    public function refresh(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $token = $user->currentAccessToken();

        if (! $token || ! $user->tokenCan(config('mobile.tokens.refresh_ability'))) {
            return ApiResponse::error('AUTH_REFRESH_INVALID', __('Refresh token không hợp lệ.'), 401);
        }

        $deviceId = $this->tokens->deviceIdFromToken($token) ?? $this->deviceId($request);
        $pair = $this->tokens->rotate($user, $deviceId); // revokes the presented refresh + old access

        return ApiResponse::success(['tokens' => $pair]);
    }

    /** POST /api/v1/auth/logout — revoke this device's mobile tokens. */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $token = $user->currentAccessToken();
        $deviceId = $token ? $this->tokens->deviceIdFromToken($token) : null;

        if ($deviceId) {
            $this->tokens->revokeDevice($user, $deviceId);
        } elseif ($token) {
            $token->delete();
        }

        return ApiResponse::success(['revoked' => true]);
    }

    private function publicUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ? substr($user->phone, 0, 3).'****'.substr($user->phone, -2) : null,
            'kyc_status' => $user->kyc_status,
            'abilities' => $user->tokenAbilities(),
        ];
    }

    private function deviceId(Request $request): string
    {
        $deviceId = $request->header('X-Device-Id') ?: $request->input('device_id');
        if (! $deviceId) {
            throw ValidationException::withMessages(['device_id' => __('Thiếu X-Device-Id.')]);
        }

        return $deviceId;
    }
}
