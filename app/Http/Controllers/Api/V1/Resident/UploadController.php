<?php

namespace App\Http\Controllers\Api\V1\Resident;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\AttachmentResource;
use App\Models\Attachment;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Upload ảnh của cư dân. Trả về attachment (id + url) CHƯA gắn phiếu; khi tạo
 * phiếu/bình luận, client gửi kèm `attachment_ids[]` để link. Ảnh mồ côi có thể
 * dọn định kỳ.
 */
class UploadController extends ApiController
{
    private const MAX_KB = 8192; // 8MB / ảnh

    public function __construct(
        private readonly \App\Services\Media\ImageVariantService $variants,
    ) {}

    /** POST /resident/uploads (multipart) — field `file`. */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:'.self::MAX_KB],
        ]);

        $user = $request->user();
        $file = $request->file('file');
        $dir = 'resident-uploads/'.($user->id ?? 'anon');
        $path = $file->store($dir, 'public');

        // Sinh thumb/feed/original + đọc kích thước thật (đã áp cờ xoay EXIF).
        // Hỏng ở khâu này thì vẫn giữ ảnh gốc: mất tối ưu chứ không mất bài đăng.
        $meta = null;
        try {
            $meta = $this->variants->generate($file, 'public', $path);
        } catch (\Throwable $e) {
            report($e);
        }

        $attachment = Attachment::create([
            'tenant_id' => $user->tenant_id,
            'disk' => 'public',
            'path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $meta['width'] ?? null,
            'height' => $meta['height'] ?? null,
            'variants' => $meta['variants'] ?? null,
            'uploaded_by' => $user->id,
        ]);

        return ApiResponse::success(
            AttachmentResource::make($attachment)->resolve($request),
            [],
            201,
        );
    }
}
