<?php

declare(strict_types=1);

namespace App\Services\Localization;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class LocaleResolver
{
    public function resolve(
        Request $request,
        ?int $userId = null,
        ?int $tenantId = null,
        ?int $projectId = null,
        ?string $explicitLocale = null,
    ): string {
        $supported = $this->supportedLocales($tenantId, $projectId);
        $fallback = $this->tenantDefaultLocale($tenantId, $projectId);

        $candidates = [
            $explicitLocale,
            $this->userPreference($userId),
            $request->header((string) config('localization.device_header', 'X-Device-Locale')),
            $this->acceptLanguageMatch($request, $supported),
            $fallback,
            (string) config('localization.fallback_locale', 'vi-VN'),
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalize($candidate);

            if ($normalized !== null && $supported->contains($normalized)) {
                return $normalized;
            }
        }

        return 'vi-VN';
    }

    /**
     * @return Collection<int, string>
     */
    public function supportedLocales(?int $tenantId, ?int $projectId): Collection
    {
        $cacheKey = sprintf('i18n:supported:%s:%s', $tenantId ?? 'platform', $projectId ?? 'all');

        // Cache a plain array, not a Collection: serializing-cache drivers (file/database/
        // redis) round-trip a cached Collection object into an __PHP_Incomplete_Class on
        // read (the array cache driver used in tests hides this). Wrap in collect() after.
        $codes = Cache::remember($cacheKey, 300, function () use ($tenantId, $projectId): array {
            if ($projectId !== null) {
                $project = DB::table('project_locale_settings')
                    ->where('project_id', $projectId)
                    ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
                    ->first();

                $projectLocales = $this->decodeLocales($project?->supported_locales ?? null);

                if ($projectLocales->isNotEmpty()) {
                    return $this->onlyEnabled($projectLocales)->all();
                }
            }

            if ($tenantId !== null) {
                $tenant = DB::table('tenant_locale_settings')->where('tenant_id', $tenantId)->first();
                $tenantLocales = $this->decodeLocales($tenant?->supported_locales ?? null);

                if ($tenantLocales->isNotEmpty()) {
                    return $this->onlyEnabled($tenantLocales)->all();
                }
            }

            return DB::table('locales')
                ->where('enabled', true)
                ->orderBy('sort_order')
                ->pluck('code')
                ->all();
        });

        return collect($codes);
    }

    public function assertSupported(string $locale, ?int $tenantId, ?int $projectId): string
    {
        $normalized = $this->normalize($locale);

        if ($normalized === null || !$this->supportedLocales($tenantId, $projectId)->contains($normalized)) {
            throw new \InvalidArgumentException("Unsupported locale: {$locale}");
        }

        return $normalized;
    }

    /**
     * Best supported match from a client-sent Accept-Language header.
     *
     * Only honored when the client actually sent the header, and only for locales that
     * are enabled + tenant-supported. Returning null (header absent or no match) lets the
     * tenant/platform/vi-VN defaults take over, instead of leaking the server's own locale
     * the way Request::getPreferredLanguage() does when given a non-empty fallback list.
     *
     * @param Collection<int, string> $supported
     */
    private function acceptLanguageMatch(Request $request, Collection $supported): ?string
    {
        if (! $request->headers->has('Accept-Language')) {
            return null;
        }

        foreach ($request->getLanguages() as $language) {
            $normalized = $this->normalize($language);

            if ($normalized !== null && $supported->contains($normalized)) {
                return $normalized;
            }
        }

        return null;
    }

    private function userPreference(?int $userId): ?string
    {
        if ($userId === null) {
            return null;
        }

        $preference = DB::table('user_locale_preferences')->where('user_id', $userId)->first();

        if ($preference === null || (bool) $preference->follow_device) {
            return null;
        }

        return $preference->locale;
    }

    private function tenantDefaultLocale(?int $tenantId, ?int $projectId): string
    {
        if ($projectId !== null) {
            $projectLocale = DB::table('project_locale_settings')
                ->where('project_id', $projectId)
                ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
                ->value('default_locale');

            if (is_string($projectLocale) && $projectLocale !== '') {
                return $projectLocale;
            }
        }

        if ($tenantId !== null) {
            $tenantLocale = DB::table('tenant_locale_settings')
                ->where('tenant_id', $tenantId)
                ->value('default_locale');

            if (is_string($tenantLocale) && $tenantLocale !== '') {
                return $tenantLocale;
            }
        }

        return (string) config('localization.default_locale', 'vi-VN');
    }

    /**
     * @return Collection<int, string>
     */
    private function decodeLocales(mixed $value): Collection
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return collect(is_array($value) ? $value : [])
            ->filter(fn ($locale): bool => is_string($locale) && $locale !== '')
            ->values();
    }

    /**
     * @param Collection<int, string> $locales
     * @return Collection<int, string>
     */
    private function onlyEnabled(Collection $locales): Collection
    {
        $enabled = DB::table('locales')
            ->where('enabled', true)
            ->whereIn('code', $locales->all())
            ->pluck('code');

        return $locales->filter(fn (string $locale): bool => $enabled->contains($locale))->values();
    }

    private function normalize(?string $locale): ?string
    {
        if ($locale === null || trim($locale) === '') {
            return null;
        }

        $locale = str_replace('_', '-', trim($locale));
        [$language, $region] = array_pad(explode('-', $locale, 2), 2, null);

        if ($region === null) {
            $mapping = [
                'vi' => 'vi-VN',
                'en' => 'en-US',
                'ko' => 'ko-KR',
                'ja' => 'ja-JP',
                'zh' => 'zh-CN',
            ];

            return $mapping[strtolower($language)] ?? null;
        }

        return strtolower($language).'-'.strtoupper($region);
    }
}
