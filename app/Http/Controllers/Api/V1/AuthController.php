<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
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
