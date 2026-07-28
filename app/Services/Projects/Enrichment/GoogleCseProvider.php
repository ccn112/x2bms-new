<?php

namespace App\Services\Projects\Enrichment;

use App\Models\PublicProject;
use Illuminate\Support\Facades\Http;

/**
 * Google Programmable Search (Custom Search JSON API).
 * Cần GOOGLE_CSE_KEY + GOOGLE_CSE_CX. searchType=image cho ảnh, web thường cho info.
 * https://developers.google.com/custom-search/v1/using_rest
 */
class GoogleCseProvider implements EnrichmentProvider
{
    public function __construct(
        private ?string $key,
        private ?string $cx,
        private int $timeout = 20,
    ) {}

    public function name(): string
    {
        return 'google_cse';
    }

    private function assertConfigured(): void
    {
        if (empty($this->key) || empty($this->cx)) {
            throw new \RuntimeException('Chưa cấu hình GOOGLE_CSE_KEY/GOOGLE_CSE_CX.');
        }
    }

    public function searchImages(PublicProject $project): array
    {
        $this->assertConfigured();
        $q = $this->imageQuery($project);
        $resp = Http::timeout($this->timeout)->get('https://www.googleapis.com/customsearch/v1', [
            'key' => $this->key, 'cx' => $this->cx, 'q' => $q,
            'searchType' => 'image', 'num' => 8, 'safe' => 'active',
        ]);
        if (! $resp->successful()) {
            return [];
        }
        $out = [];
        foreach ($resp->json('items', []) as $it) {
            $out[] = [
                'url'         => $it['link'] ?? '',
                'thumb'       => $it['image']['thumbnailLink'] ?? ($it['link'] ?? ''),
                'source_page' => $it['image']['contextLink'] ?? ($it['displayLink'] ?? ''),
                'title'       => $it['title'] ?? '',
            ];
        }

        return array_values(array_filter($out, fn ($x) => $x['url'] !== ''));
    }

    public function searchInfo(PublicProject $project): array
    {
        $this->assertConfigured();
        $q = $this->infoQuery($project);
        $resp = Http::timeout($this->timeout)->get('https://www.googleapis.com/customsearch/v1', [
            'key' => $this->key, 'cx' => $this->cx, 'q' => $q, 'num' => 5,
        ]);
        if (! $resp->successful()) {
            return [];
        }
        $out = [];
        foreach ($resp->json('items', []) as $it) {
            $out[] = [
                'snippet'    => $it['snippet'] ?? '',
                'source_url' => $it['link'] ?? '',
                'title'      => $it['title'] ?? '',
            ];
        }

        return array_values(array_filter($out, fn ($x) => $x['source_url'] !== ''));
    }

    private function imageQuery(PublicProject $p): string
    {
        return trim(($p->name ?? '').' '.($p->developer_name ?? '').' phối cảnh dự án');
    }

    private function infoQuery(PublicProject $p): string
    {
        return trim(($p->name ?? '').' '.($p->developer_name ?? '').' chủ đầu tư dự án');
    }
}
