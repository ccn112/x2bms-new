<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * PATCH /api/v1/me/profile — người dùng tự cập nhật hồ sơ tài khoản (person-level).
 *
 * Chỉ sửa các field AN TOÀN của `users` (name/phone/email/gender/dob/nationality).
 * Dữ liệu KYC (id_no, kyc_status, kyc_verified_at) KHÔNG sửa ở đây — đi qua luồng
 * xác thực riêng. Partial update: chỉ áp field được gửi lên. Ảnh đại diện
 * (avatar) upload qua luồng multipart riêng: POST/DELETE /api/v1/me/avatar. Xem
 * docs/contracts/RESIDENT_API_DOMAIN.md.
 */
class ProfileController extends ApiController
{
    /** Disk lưu avatar — person-level, public (giống Resident), KHÔNG qua TenantStorage. */
    private const AVATAR_DISK = 'public';
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

        return ApiResponse::success(['user' => $this->userPayload($user)]);
    }

    /**
     * POST /api/v1/me/avatar — tải/đổi ảnh đại diện (multipart, field `avatar`).
     *
     * Lưu trên disk `public` tại `avatars/users/{id}/…` (person-level, không tenant-
     * scoped — cùng khuôn mặt ở mọi tenant). Ghi `avatar_path` cho user VÀ đồng bộ
     * sang mọi resident membership của người này (để list thành viên hộ/cộng đồng
     * hiển thị đúng). Xoá file cũ để không rác. Trả `avatar_url` tuyệt đối.
     */
    public function avatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $old = $user->avatar_path;

        $path = $request->file('avatar')->store("avatars/users/{$user->id}", self::AVATAR_DISK);

        DB::transaction(function () use ($user, $path): void {
            $user->forceFill(['avatar_path' => $path])->save();
            // Đồng bộ sang resident rows đã liên kết (bỏ qua tenant scope — dữ liệu của
            // chính người này) để avatar hiện nhất quán ở mọi ngữ cảnh.
            $user->residentMemberships()->update(['avatar_path' => $path]);
        });

        if ($old && $old !== $path) {
            Storage::disk(self::AVATAR_DISK)->delete($old);
        }

        return ApiResponse::success([
            'avatar_url' => $user->refresh()->avatar_url,
            'user' => $this->userPayload($user),
        ]);
    }

    /** DELETE /api/v1/me/avatar — gỡ ảnh đại diện, quay về avatar chữ cái. */
    public function removeAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $old = $user->avatar_path;

        DB::transaction(function () use ($user): void {
            $user->forceFill(['avatar_path' => null])->save();
            $user->residentMemberships()->update(['avatar_path' => null]);
        });

        if ($old) {
            Storage::disk(self::AVATAR_DISK)->delete($old);
        }

        return ApiResponse::success([
            'avatar_url' => $user->refresh()->avatar_url,
            'user' => $this->userPayload($user),
        ]);
    }

    /** Payload user person-level dùng chung cho update/avatar (khớp bootstrap.user). */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'dob' => $user->dob?->toDateString(),
            'nationality' => $user->nationality,
            'kyc_status' => $user->kyc_status,
            'avatar_url' => $user->avatar_url,
        ];
    }
}
