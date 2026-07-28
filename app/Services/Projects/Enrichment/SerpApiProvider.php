<?php

namespace App\Services\Projects\Enrichment;

use App\Models\PublicProject;
use Illuminate\Support\Facades\Http;

/**
 * SerpApi (Google Images/Search). Cần SERPAPI_KEY. https://serpapi.com/
 */
class SerpApiProvider implements EnrichmentProvider
{
    public function __construct(
        private ?string $key,
        private int $timeout = 20,
    ) {}

    public function name(): string
    {
        return 'serpapi';
    }

    private function assertConfigured(): void
    {
        if (empty($this->key)) {
            throw new \RuntimeException('Chưa cấu hình SERPAPI_KEY.');
        }
    }

    public function searchImages(PublicProject $project): array
    {
        $this->assertConfigured();
        $resp = Http::timeout($this->timeout)->get('https://serpapi.com/search.json', [
            'engine' => 'google_images', 'q' => $this->query($project, 'phối cảnh dự án'),
            'api_key' => $this->key, 'num' => 8,
        ]);
        if (! $resp->successful()) {
            return [];
        }
        $out = [];
        foreach ($resp->json('images_results', []) as $it) {
            $url = $it['original'] ?? ($it['thumbnail'] ?? '');
            if ($url === '') {
                continue;
            }
            $out[] = [
                'url'         => $url,
                'thumb'       => $it['thumbnail'] ?? $url,
                'source_page' => $it['link'] ?? ($it['source'] ?? ''),
                'title'       => $it['title'] ?? '',
            ];
        }

        return $out;
    }

    public function searchInfo(PublicProject $project): array
    {
        $this->assertConfigured();
        $resp = Http::timeout($this->timeout)->get('https://serpapi.com/search.json', [
            'engine' => 'google', 'q' => $this->query($project, 'chủ đầu tư dự án'),
            'api_key' => $this->key, 'num' => 5,
        ]);
        if (! $resp->successful()) {
            return [];
        }
        $out = [];
        foreach ($resp->json('organic_results', []) as $it) {
            $link = $it['link'] ?? '';
            if ($link === '') {
                continue;
            }
            $out[] = [
                'snippet'    => $it['snippet'] ?? '',
                'source_url' => $link,
                'title'      => $it['title'] ?? '',
            ];
        }

        return $out;
    }

    private function query(PublicProject $p, string $suffix): string
    {
        return trim(($p->name ?? '').' '.($p->developer_name ?? '').' '.$suffix);
    }
}
