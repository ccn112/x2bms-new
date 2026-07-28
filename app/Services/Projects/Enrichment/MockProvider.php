<?php

namespace App\Services\Projects\Enrichment;

use App\Models\PublicProject;
use Illuminate\Support\Str;

/**
 * Provider GIẢ LẬP — trả ứng viên ảnh (picsum) + info nguồn giả để UI chạy KHÔNG cần key.
 * Dùng mặc định (ENRICH_PROVIDER=mock). Thay bằng google_cse/serpapi khi có key.
 */
class MockProvider implements EnrichmentProvider
{
    public function name(): string
    {
        return 'mock';
    }

    public function searchImages(PublicProject $project): array
    {
        $seed = Str::slug($project->name ?: ('pj'.$project->id)) ?: ('pj'.$project->id);
        $n = 5;
        $out = [];
        for ($i = 1; $i <= $n; $i++) {
            $url = "https://picsum.photos/seed/{$seed}-{$i}/1024/640";
            $out[] = [
                'url'         => $url,
                'thumb'       => "https://picsum.photos/seed/{$seed}-{$i}/320/200",
                'source_page' => 'https://example-official.vn/du-an/'.$seed.'#img'.$i,
                'title'       => 'Phối cảnh '.($project->name ?: 'dự án').' ('.$i.')',
            ];
        }

        return $out;
    }

    public function searchInfo(PublicProject $project): array
    {
        $name = $project->name ?: 'Dự án';
        $dev = $project->developer_name ? ' do '.$project->developer_name.' làm chủ đầu tư' : '';

        return [
            [
                'snippet'    => $name.$dev.'. Thông tin mô tả tổng quan (nguồn giả lập để demo — thay bằng nguồn thật khi cắm key).',
                'source_url' => 'https://example-official.vn/du-an/'.Str::slug($name),
                'title'      => $name.' — Trang chủ dự án (mock)',
            ],
            [
                'snippet'    => 'Tiến độ & pháp lý '.$name.': dữ liệu tham khảo (mock).',
                'source_url' => 'https://baochinhthong.vn/'.Str::slug($name),
                'title'      => 'Bài viết về '.$name.' (mock)',
            ],
            [
                'snippet'    => 'Mặt bằng & tiện ích '.$name.' (mock).',
                'source_url' => 'https://cafeland.vn/du-an/'.Str::slug($name),
                'title'      => $name.' trên trang tin BĐS (mock)',
            ],
        ];
    }
}
