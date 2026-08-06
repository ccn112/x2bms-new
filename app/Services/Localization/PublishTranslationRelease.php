<?php

declare(strict_types=1);

namespace App\Services\Localization;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Publishes and rolls back immutable translation releases (packs) for the resident
 * app's remote-pack pipeline.
 *
 * Publish snapshots the current PUBLISHED product-scope values for a namespace+locale,
 * computes the checksum with {@see TranslationPackChecksum} (identical canonical JSON to
 * the Dart verifier), inserts a `translation_releases` row (status=published) plus its
 * `translation_release_items`, and busts the pack cache. Published releases are NEVER
 * mutated — a new version supersedes; a rollback flips status to 'rolled_back' so the
 * previous published release becomes the active/latest again.
 *
 * Mirrors the reference algorithm in LocalizationMasterSeeder::publishSeedReleases().
 */
final class PublishTranslationRelease
{
    public function __construct(
        private readonly TranslationPackChecksum $checksum,
        private readonly TranslationPackService $packService,
    ) {}

    /**
     * Snapshot + publish a new release for one namespace+locale. Returns the release id.
     */
    public function publish(string $namespaceCode, string $locale, ?string $version = null): int
    {
        return DB::transaction(function () use ($namespaceCode, $locale, $version): int {
            $namespaceId = DB::table('translation_namespaces')
                ->where('code', $namespaceCode)
                ->value('id');

            if ($namespaceId === null) {
                throw new RuntimeException("Unknown translation namespace: {$namespaceCode}");
            }

            $version ??= 'rel-'.now()->format('Ymd-His');

            // Snapshot: current published product-scope values for this namespace+locale.
            $values = DB::table('translation_values as values')
                ->join('translation_keys as keys', 'keys.id', '=', 'values.translation_key_id')
                ->where('keys.namespace_id', $namespaceId)
                ->where('values.locale', $locale)
                ->where('values.scope_type', 'product')
                ->where('values.scope_id', '')
                ->where('values.status', 'published')
                ->orderBy('keys.key')
                ->get([
                    'values.translation_key_id',
                    'keys.key',
                    'values.value',
                ]);

            if ($values->isEmpty()) {
                throw new RuntimeException("No published values to release for {$namespaceCode}/{$locale}");
            }

            // Canonical checksum — MUST match the app's Dart verifier (same canonical JSON).
            $canonicalValues = $values
                ->mapWithKeys(fn ($row): array => [(string) $row->key => (string) $row->value])
                ->all();
            $checksum = $this->checksum->hash($canonicalValues);

            $releaseId = DB::table('translation_releases')->insertGetId([
                'namespace_id' => $namespaceId,
                'locale' => $locale,
                'version' => $version,
                'scope_type' => 'product',
                'scope_id' => '',
                'checksum' => $checksum,
                'status' => 'published',
                'published_by' => Auth::id(),
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rows = $values->map(fn ($row): array => [
                'release_id' => $releaseId,
                'translation_key_id' => $row->translation_key_id,
                'value' => $row->value,
                'value_checksum' => hash('sha256', (string) $row->value),
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            DB::table('translation_release_items')->insert($rows);

            // Bust cached pack AND the bootstrap pack_versions map so the delivery API and
            // /me/bootstrap both surface the new version immediately (the app detects the
            // change from pack_versions, so this cache must not lag behind a publish).
            $this->packService->forget($namespaceCode, $locale);
            Cache::forget("i18n:pack_versions:{$locale}");

            AuditLog::create([
                'tenant_id' => Auth::user()?->tenant_id,
                'building_id' => Auth::user()?->building_id,
                'user_id' => Auth::id(),
                'actor_name' => Auth::user()?->name,
                'action' => 'translation.release.published',
                'subject_type' => 'translation_release',
                'subject_id' => $releaseId,
                'description' => "Phát hành gói {$namespaceCode}/{$locale} phiên bản {$version} ({$values->count()} khóa, checksum ".substr($checksum, 0, 12).')',
            ]);

            return $releaseId;
        });
    }

    /**
     * Roll back a published release: flip status to 'rolled_back' (never delete) so the
     * previous published release becomes active/latest again, then bust the pack cache.
     */
    public function rollback(int $releaseId): void
    {
        DB::transaction(function () use ($releaseId): void {
            $release = DB::table('translation_releases as releases')
                ->join('translation_namespaces as namespaces', 'namespaces.id', '=', 'releases.namespace_id')
                ->where('releases.id', $releaseId)
                ->select([
                    'releases.id',
                    'releases.locale',
                    'releases.version',
                    'releases.status',
                    'namespaces.code as namespace_code',
                ])
                ->first();

            if ($release === null) {
                throw new RuntimeException("Release not found: {$releaseId}");
            }

            if ($release->status !== 'published') {
                throw new RuntimeException('Chỉ có thể khôi phục gói đang ở trạng thái published.');
            }

            DB::table('translation_releases')
                ->where('id', $releaseId)
                ->update(['status' => 'rolled_back', 'updated_at' => now()]);

            $this->packService->forget($release->namespace_code, $release->locale);
            Cache::forget("i18n:pack_versions:{$release->locale}");

            AuditLog::create([
                'tenant_id' => Auth::user()?->tenant_id,
                'building_id' => Auth::user()?->building_id,
                'user_id' => Auth::id(),
                'actor_name' => Auth::user()?->name,
                'action' => 'translation.release.rolled_back',
                'subject_type' => 'translation_release',
                'subject_id' => $releaseId,
                'description' => "Khôi phục gói {$release->namespace_code}/{$release->locale} phiên bản {$release->version}",
            ]);
        });
    }
}
