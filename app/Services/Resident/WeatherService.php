<?php

namespace App\Services\Resident;

use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thời tiết hôm nay cho card Home (HOME-01). Backend proxy Open-Meteo Forecast
 * (free, không key) theo `projects.latitude/longitude`, CACHE theo project
 * (TTL config('services.weather.cache_ttl')) — giấu vị trí + tránh rate-limit.
 *
 * Map: weather_code (WMO) → text tiếng Việt; wind_speed (km/h) → Beaufort "Cấp X".
 * Trả `null` khi project thiếu toạ độ / API lỗi (Home ẩn card, không vỡ).
 * ⚠️ Open-Meteo free = phi thương mại → cân nhắc gói/khoá khi lên prod (ENV WEATHER_*).
 */
class WeatherService
{
    /**
     * @return array{location:string,temp_c:int,condition:string,temp_max:int,temp_min:int,humidity:int,wind_label:string,code:int}|null
     */
    public function forProject(int $projectId): ?array
    {
        $project = Project::withoutGlobalScopes()->find($projectId);
        if ($project === null || $project->latitude === null || $project->longitude === null) {
            return null;
        }

        $ttl = (int) config('services.weather.cache_ttl', 1800);
        $cacheKey = "resident:weather:project:{$projectId}";

        return Cache::remember($cacheKey, $ttl, function () use ($project) {
            try {
                $response = Http::timeout(6)->get(config('services.weather.base_url'), [
                    'latitude' => $project->latitude,
                    'longitude' => $project->longitude,
                    'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m',
                    'daily' => 'temperature_2m_max,temperature_2m_min',
                    'timezone' => 'auto',
                    'forecast_days' => 1,
                ]);

                if (! $response->ok() || $response->json('current.temperature_2m') === null) {
                    return null;
                }

                $code = (int) ($response->json('current.weather_code') ?? 0);

                return [
                    'location' => (string) ($project->city ?? $project->name ?? ''),
                    'temp_c' => (int) round((float) $response->json('current.temperature_2m')),
                    'condition' => $this->conditionVi($code),
                    'temp_max' => (int) round((float) ($response->json('daily.temperature_2m_max.0') ?? $response->json('current.temperature_2m'))),
                    'temp_min' => (int) round((float) ($response->json('daily.temperature_2m_min.0') ?? $response->json('current.temperature_2m'))),
                    'humidity' => (int) round((float) ($response->json('current.relative_humidity_2m') ?? 0)),
                    'wind_label' => $this->beaufort((float) ($response->json('current.wind_speed_10m') ?? 0)),
                    'code' => $code,
                ];
            } catch (\Throwable $e) {
                Log::warning('Weather fetch failed', ['project' => $project->id, 'error' => $e->getMessage()]);

                return null;
            }
        });
    }

    /** WMO weather_code → mô tả tiếng Việt (Open-Meteo). */
    private function conditionVi(int $code): string
    {
        return match (true) {
            $code === 0 => 'Trời quang',
            $code === 1 => 'Chủ yếu quang',
            $code === 2 => 'Có mây',
            $code === 3 => 'Nhiều mây',
            in_array($code, [45, 48], true) => 'Sương mù',
            in_array($code, [51, 53, 55], true) => 'Mưa phùn',
            in_array($code, [56, 57], true) => 'Mưa phùn lạnh',
            in_array($code, [61, 63, 65], true) => 'Mưa',
            in_array($code, [66, 67], true) => 'Mưa lạnh',
            in_array($code, [71, 73, 75, 77], true) => 'Tuyết',
            in_array($code, [80, 81, 82], true) => 'Mưa rào',
            in_array($code, [85, 86], true) => 'Mưa tuyết',
            $code === 95 => 'Dông',
            in_array($code, [96, 99], true) => 'Dông kèm mưa đá',
            default => 'Không rõ',
        };
    }

    /** Wind speed (km/h) → thang Beaufort "Cấp X". */
    private function beaufort(float $kmh): string
    {
        $level = match (true) {
            $kmh < 1 => 0,
            $kmh < 6 => 1,
            $kmh < 12 => 2,
            $kmh < 20 => 3,
            $kmh < 29 => 4,
            $kmh < 39 => 5,
            $kmh < 50 => 6,
            $kmh < 62 => 7,
            $kmh < 75 => 8,
            $kmh < 89 => 9,
            $kmh < 103 => 10,
            $kmh < 118 => 11,
            default => 12,
        };

        return "Cấp {$level}";
    }
}
