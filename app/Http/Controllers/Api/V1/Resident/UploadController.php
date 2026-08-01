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

    /** Video/PDF (chỉ khi caller gửi `kind=media`, vd đính kèm phản ánh). */
    private const MAX_KB_MEDIA = 51200; // 50MB

    private const MEDIA_MIMES =
        'image/jpeg,image/png,image/webp,image/heic,image/heif,image/gif,'
        .'video/mp4,video/quicktime,application/pdf';

    public function __construct(
        private readonly \App\Services\Media\ImageVariantService $variants,
    ) {}

    /**
     * POST /resident/uploads (multipart) — field `file`.
     *
     * Mặc định CHỈ nhận ảnh (≤8MB) — giữ nguyên cho slip comment/community.
     * Gửi `kind=media` để nhận thêm video/PDF (≤50MB) — dùng cho đính kèm phản ánh.
     * Đây là opt-in nên không nới quyền cho caller cũ.
     */
    public function store(Request $request): JsonResponse
    {
        $isMedia = $request->input('kind') === 'media';
        $request->validate([
            'file' => $isMedia
                ? ['required', 'file', 'max:'.self::MAX_KB_MEDIA, 'mimetypes:'.self::MEDIA_MIMES]
                : ['required', 'file', 'image', 'max:'.self::MAX_KB],
        ]);

        $user = $request->user();
        $file = $request->file('file');
        $dir = 'resident-uploads/'.($user->id ?? 'anon');
        $path = $file->store($dir, 'public');

        // Sinh thumb/feed/original + đọc kích thước thật CHỈ cho ảnh (video/PDF
        // không có biến thể). Hỏng ở khâu này thì vẫn giữ file gốc.
        $meta = null;
        if (str_starts_with((string) $file->getMimeType(), 'image/')) {
            try {
                $meta = $this->variants->generate($file, 'public', $path);
            } catch (\Throwable $e) {
                report($e);
            }
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
