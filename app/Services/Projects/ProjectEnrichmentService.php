<?php

namespace App\Services\Projects;

use App\Models\PublicProject;
use App\Services\Projects\Enrichment\EnrichmentProvider;
use App\Services\Projects\Enrichment\GoogleCseProvider;
use App\Services\Projects\Enrichment\MockProvider;
use App\Services\Projects\Enrichment\SerpApiProvider;

/**
 * "Tìm ảnh & thông tin" chính thống cho dự án — admin duyệt ứng viên rồi lưu.
 * Chọn provider theo config('enrichment.provider'): mock | google_cse | serpapi.
 */
class ProjectEnrichmentService
{
    public function provider(): EnrichmentProvider
    {
        $timeout = (int) config('enrichment.timeout', 20);

        return match (config('enrichment.provider', 'mock')) {
            'google_cse' => new GoogleCseProvider(
                config('enrichment.google_cse.key'),
                config('enrichment.google_cse.cx'),
                $timeout,
            ),
            'serpapi' => new SerpApiProvider(config('enrichment.serpapi.key'), $timeout),
            default   => new MockProvider(),
        };
    }

    /** @return array<int,array{url:string,thumb:string,source_page:string,title:string}> */
    public function searchImages(PublicProject $project): array
    {
        return array_slice($this->provider()->searchImages($project), 0, (int) config('enrichment.max_images', 8));
    }

    /** @return array<int,array{snippet:string,source_url:string,title:string}> */
    public function searchInfo(PublicProject $project): array
    {
        return array_slice($this->provider()->searchInfo($project), 0, (int) config('enrichment.max_info', 5));
    }

    /**
     * Áp lựa chọn của admin vào dự án.
     *
     * @param  array<int,string>  $images  URL ảnh gallery đã chọn
     * @param  ?string  $cover  URL ảnh bìa
     * @param  array<int,array{snippet:string,source_url:string,title:string}>  $infos  info đã chọn
     */
    public function applySelection(PublicProject $project, array $images, ?string $cover, array $infos, string $providerName): PublicProject
    {
        $meta = (array) $project->metadata_json;

        $images = array_values(array_unique(array_filter($images)));
        if ($images !== []) {
            $meta['official_images'] = $images;
            $meta['official_cover'] = $cover ?: $images[0];
        } elseif ($cover) {
            $meta['official_cover'] = $cover;
            $meta['official_images'] = [$cover];
        }

        $update = [];
        if ($infos !== []) {
            // Nguồn chính thống + bổ sung mô tả (không ghi đè mô tả cũ, nối thêm).
            $meta['official_url'] = $infos[0]['source_url'] ?? ($meta['official_url'] ?? null);
            $meta['official_info'] = array_map(fn ($i) => [
                'snippet'    => $i['snippet'] ?? '',
                'source_url' => $i['source_url'] ?? '',
                'title'      => $i['title'] ?? '',
            ], $infos);

            $addText = collect($infos)->pluck('snippet')->filter()->implode("\n");
            if ($addText !== '') {
                $desc = trim((string) $project->description);
                $update['description'] = $desc === '' ? $addText : $desc."\n\n[Nguồn chính thống]\n".$addText;
            }
        }

        $meta['enrichment_log'][] = [
            'at'       => now()->toIso8601String(),
            'provider' => $providerName,
            'images'   => count($images),
            'infos'    => count($infos),
            'sources'  => array_values(array_filter(array_map(fn ($i) => $i['source_url'] ?? null, $infos))),
        ];

        $update['metadata_json'] = $meta;
        $project->update($update);

        return $project;
    }
}
