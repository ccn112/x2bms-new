<?php

namespace App\Services\Projects\Enrichment;

use App\Models\PublicProject;

/** Nhà cung cấp tìm ảnh & thông tin chính thống cho dự án. */
interface EnrichmentProvider
{
    /**
     * @return array<int,array{url:string,thumb:string,source_page:string,title:string}>
     */
    public function searchImages(PublicProject $project): array;

    /**
     * @return array<int,array{snippet:string,source_url:string,title:string}>
     */
    public function searchInfo(PublicProject $project): array;

    /** Tên provider (để ghi nhật ký). */
    public function name(): string;
}
