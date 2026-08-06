<?php

declare(strict_types=1);

namespace App\Services\Localization;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class TranslationPackService
{
    /**
     * @return array{namespace:string,locale:string,version:string,checksum:string,values:array<string,string>}
     */
    public function getPublishedPack(
        string $namespace,
        string $locale,
        string $scopeType = 'product',
        string $scopeId = '',
    ): array {
        $cacheKey = implode(':', ['i18n', 'pack', $namespace, $locale, $scopeType, $scopeId ?: 'global']);

        return Cache::remember(
            $cacheKey,
            (int) config('localization.remote_pack.cache_ttl_seconds', 3600),
            function () use ($namespace, $locale, $scopeType, $scopeId): array {
                $release = DB::table('translation_releases as releases')
                    ->join('translation_namespaces as namespaces', 'namespaces.id', '=', 'releases.namespace_id')
                    ->where('namespaces.code', $namespace)
                    ->where('releases.locale', $locale)
                    ->where('releases.scope_type', $scopeType)
                    ->where('releases.scope_id', $scopeId)
                    ->where('releases.status', 'published')
                    ->orderByDesc('releases.published_at')
                    ->orderByDesc('releases.id')
                    ->select([
                        'releases.id',
                        'releases.version',
                        'releases.checksum',
                    ])
                    ->first();

                if ($release === null) {
                    throw new \RuntimeException("No published translation pack for {$namespace}/{$locale}");
                }

                $values = DB::table('translation_release_items as items')
                    ->join('translation_keys as keys', 'keys.id', '=', 'items.translation_key_id')
                    ->where('items.release_id', $release->id)
                    ->orderBy('keys.key')
                    ->pluck('items.value', 'keys.key')
                    ->map(fn ($value): string => (string) $value)
                    ->all();

                return [
                    'namespace' => $namespace,
                    'locale' => $locale,
                    'version' => (string) $release->version,
                    'checksum' => (string) $release->checksum,
                    'values' => $values,
                ];
            }
        );
    }

    public function forget(string $namespace, string $locale, string $scopeType = 'product', string $scopeId = ''): void
    {
        Cache::forget(implode(':', ['i18n', 'pack', $namespace, $locale, $scopeType, $scopeId ?: 'global']));
    }
}
