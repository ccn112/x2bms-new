<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Auth\TokenService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpController extends ApiController
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly TokenService $tokens,
    ) {}

    /** POST /api/v1/auth/otp/request */
    public function request(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'in:phone,email'],
            'destination' => ['required', 'string'],
            'purpose' => ['required', 'in:register,login,resident_activation'],
        ]);

        $result = $this->otp->request($data['channel'], $data['destination'], $data['purpose']);

        $payload = ['sent' => $result['sent'], 'expires_in' => $result['expires_in']];
        if ($result['dev_code'] !== null) {
            $payload['dev_code'] = $result['dev_code']; // non-production only
        }

        return ApiResponse::success($payload);
    }

    /** POST /api/v1/auth/otp/verify — on login purpose, an existing user gets a token pair. */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'in:phone,email'],
            'destination' => ['required', 'string'],
            'purpose' => ['required', 'in:register,login,resident_activation'],
            'code' => ['required', 'string'],
        ]);

        $check = $this->otp->verify($data['channel'], $data['destination'], $data['purpose'], $data['code']);
        if (! $check['valid']) {
            $retryable = $check['reason'] === 'mismatch';
            return ApiResponse::error('OTP_'.strtoupper($check['reason']), __('Mã OTP không hợp lệ.'), 422, retryable: $retryable);
        }

        if ($data['purpose'] === 'login') {
            $column = $data['channel'] === 'email' ? 'email' : 'phone';
            $user = User::where($column, $data['destination'])->first();
            if (! $user) {
                return ApiResponse::error('AUTH_USER_NOT_FOUND', __('Chưa có tài khoản.'), 404);
            }
            $deviceId = $request->header('X-Device-Id') ?: $request->input('device_id', 'unknown');

            return ApiResponse::success(['verified' => true, 'tokens' => $this->tokens->issuePair($user, $deviceId)]);
        }

        // register / resident_activation: hand back a marker; the registration endpoint (next slice) consumes it.
        return ApiResponse::success(['verified' => true]);
    }
}
