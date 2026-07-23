<?php

namespace App\Http\Controllers\Api\V1;

use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * PATCH /api/v1/me/profile — người dùng tự cập nhật hồ sơ tài khoản (person-level).
 *
 * Chỉ sửa các field AN TOÀN của `users` (name/phone/email/gender/dob/nationality).
 * Dữ liệu KYC (id_no, kyc_status, kyc_verified_at) KHÔNG sửa ở đây — đi qua luồng
 * xác thực riêng. Partial update: chỉ áp field được gửi lên. Ảnh đại diện
 * (avatar_path) upload qua luồng multipart riêng (chưa làm). Xem
 * docs/contracts/RESIDENT_API_DOMAIN.md.
 */
class ProfileController extends ApiController
{
    /** PATCH /api/v1/me/profile */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => [
                'sometimes', 'required', 'email:rfc', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'gender' => ['sometimes', 'nullable', 'string', 'max:20'],
            'dob' => ['sometimes', 'nullable', 'date'],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        if (empty($data)) {
            return ApiResponse::error('no_changes', 'Không có trường nào để cập nhật.', 422);
        }

        $user->fill($data)->save();

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'dob' => $user->dob?->toDateString(),
                'nationality' => $user->nationality,
                'kyc_status' => $user->kyc_status,
            ],
        ]);
    }
}
