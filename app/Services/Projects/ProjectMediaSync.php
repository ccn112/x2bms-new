<?php

namespace App\Services\Projects;

use App\Models\ProjectMedia;
use App\Models\PublicProject;

/**
 * Materialize thư viện ảnh (ProjectMedia) từ metadata_json của dự án.
 * Dùng bởi command projects:sync-media và có thể gọi sau enrich/Tìm ảnh.
 */
class ProjectMediaSync
{
    /**
     * Đồng bộ ảnh cho 1 dự án. Trả số media MỚI tạo.
     * - official_images (source=official) đứng trước, rồi images (source=batdongsan, watermark).
     * - Dedup theo (public_project_id, file_url) qua updateOrCreate.
     * - Đặt 1 is_cover: official_cover → cover_image → ảnh đầu.
     */
    public function sync(PublicProject $project): int
    {
        $meta = (array) $project->metadata_json;
        $official = array_values(array_unique(array_filter((array) ($meta['official_images'] ?? []))));
        $bds = array_values(array_unique(array_filter((array) ($meta['images'] ?? []))));

        // Danh sách nguồn ưu tiên official trước.
        $items = [];
        foreach ($official as $u) {
            $items[$u] = ['source' => 'official', 'is_watermarked' => false];
        }
        foreach ($bds as $u) {
            if (! isset($items[$u])) {
                $items[$u] = ['source' => 'batdongsan', 'is_watermarked' => str_contains($u, '_wm')];
            }
        }

        if ($items === []) {
            return 0;
        }

        // Xác định URL ảnh bìa mong muốn.
        $coverUrl = $meta['official_cover']
            ?? ($official[0] ?? null)
            ?? ($meta['cover_image'] ?? null)
            ?? ($bds[0] ?? null)
            ?? array_key_first($items);

        $created = 0;
        $sort = (int) $project->media()->max('sort_order');
        $coverMediaId = null;

        foreach ($items as $url => $info) {
            $media = ProjectMedia::withTrashed()->firstOrNew([
                'public_project_id' => $project->id,
                'file_url'          => $url,
            ]);
            $wasExisting = $media->exists;
            if ($media->trashed()) {
                $media->restore();
            }
            $media->media_type ??= 'image';
            $media->source = $info['source'];
            $media->is_watermarked = $info['is_watermarked'];
            if (! $wasExisting) {
                $media->sort_order = ++$sort;
                $media->is_active = true;
                $created++;
            }
            $media->save();

            if ($url === $coverUrl) {
                $coverMediaId = $media->id;
            }
        }

        // Đặt is_cover đúng 1 ảnh (nếu chưa có ảnh bìa nào đang active).
        if ($coverMediaId) {
            $hasCover = $project->media()->where('is_cover', true)->exists();
            // Ưu tiên official/manual: nếu cover hiện tại là batdongsan mà có official mới thì cập nhật.
            $currentCover = $project->media()->where('is_cover', true)->first();
            $shouldReset = ! $hasCover
                || ($currentCover && $currentCover->source === 'batdongsan' && ! empty($meta['official_cover']));
            if ($shouldReset) {
                $project->media()->where('is_cover', true)->update(['is_cover' => false]);
                ProjectMedia::whereKey($coverMediaId)->update(['is_cover' => true]);
            }
        }

        return $created;
    }
}
