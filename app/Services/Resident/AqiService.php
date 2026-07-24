<?php

namespace App\Services\Resident;

use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Chỉ số chất lượng không khí (AQI) cho metric Home. Backend proxy Open-Meteo Air
 * Quality (free, không key) theo `projects.latitude/longitude` rồi CACHE theo project
 * (TTL từ config('services.aqi.cache_ttl')) — giấu vị trí + tránh rate-limit.
 *
 * Trả `null` khi project không có toạ độ hoặc gọi API lỗi (Home ẩn metric, không vỡ).
 * ⚠️ Open-Meteo free = phi thương mại → gắn key/gói khác khi lên prod (ENV AQI_*).
 * Xem docs/contracts/RESIDENT_API_DOMAIN.md §4.
 */
class AqiService
{
    /** @return array{key:string,title:string,value:int,unit:string,tone:string,label:string}|null */
    public function forProject(int $projectId): ?array
    {
        $project = Project::withoutGlobalScopes()->find($projectId);
        if ($project === null || $project->latitude === null || $project->longitude === null) {
            return null;
        }

        $ttl = (int) config('services.aqi.cache_ttl', 3600);
        $cacheKey = "resident:aqi:project:{$projectId}";

        return Cache::remember($cacheKey, $ttl, function () use ($project) {
            try {
                $response = Http::timeout(6)->get(config('services.aqi.base_url'), [
                    'latitude' => $project->latitude,
                    'longitude' => $project->longitude,
                    'current' => 'us_aqi,european_aqi',
                ]);

                if (! $response->ok()) {
                    return null;
                }

                $aqi = $response->json('current.us_aqi');
                if ($aqi === null) {
                    return null;
                }

                $aqi = (int) round($aqi);

                return [
                    'key' => 'aqi',
                    'title' => 'Chất lượng không khí',
                    'value' => $aqi,
                    'unit' => 'AQI',
                    'tone' => $this->tone($aqi),
                    'label' => $this->label($aqi),
                ];
            } catch (\Throwable $e) {
                Log::warning('AQI fetch failed', ['project' => $project->id, 'error' => $e->getMessage()]);

                return null;
            }
        });
    }

    /** Tone (app tô màu) theo ngưỡng US AQI. */
    private function tone(int $aqi): string
    {
        return match (true) {
            $aqi <= 50 => 'good',
            $aqi <= 100 => 'moderate',
            $aqi <= 150 => 'poor',
            default => 'bad',
        };
    }

    private function label(int $aqi): string
    {
        return match (true) {
            $aqi <= 50 => 'Tốt',
            $aqi <= 100 => 'Trung bình',
            $aqi <= 150 => 'Kém',
            default => 'Xấu',
        };
    }
}
