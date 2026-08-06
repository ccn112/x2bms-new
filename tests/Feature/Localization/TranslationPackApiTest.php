<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Services\Localization\TranslationPackChecksum;
use Database\Seeders\LocalizationMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * I18N-008 — published translation pack delivery API.
 *
 * Covers: pack payload + checksum integrity, ETag/If-None-Match 304, namespace filtering,
 * and rollback re-pointing the active version without deleting release history.
 */
final class TranslationPackApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LocalizationMasterSeeder::class);
    }

    public function test_pack_returns_values_with_matching_checksum(): void
    {
        $response = $this->getJson('/api/v1/localization/packs/x2.shared/vi-VN')->assertOk();

        $pack = $response->json('data');
        self::assertSame('x2.shared', $pack['namespace']);
        self::assertSame('vi-VN', $pack['locale']);
        self::assertNotEmpty($pack['values']);
        self::assertArrayHasKey('common.save', $pack['values']);

        // The published checksum must be reproducible from the returned values.
        $recomputed = app(TranslationPackChecksum::class)->hash($pack['values']);
        self::assertSame($pack['checksum'], $recomputed);

        // ETag mirrors the checksum so clients can revalidate cheaply.
        $response->assertHeader('ETag', '"'.$pack['checksum'].'"');
    }

    public function test_if_none_match_returns_304(): void
    {
        $checksum = $this->getJson('/api/v1/localization/packs/x2.shared/vi-VN')
            ->json('data.checksum');

        $this->getJson('/api/v1/localization/packs/x2.shared/vi-VN', [
            'If-None-Match' => '"'.$checksum.'"',
        ])->assertStatus(304);
    }

    public function test_namespace_filtering_returns_distinct_packs(): void
    {
        $shared = $this->getJson('/api/v1/localization/packs/x2.shared/vi-VN')->json('data.values');
        $auth = $this->getJson('/api/v1/localization/packs/x2.resident_app/vi-VN')->json('data.values');

        self::assertArrayHasKey('common.save', $shared);
        self::assertArrayNotHasKey('common.save', $auth);
        self::assertNotSame($shared, $auth);
    }

    public function test_rollback_switches_active_version_without_deleting_history(): void
    {
        $namespaceId = DB::table('translation_namespaces')->where('code', 'x2.shared')->value('id');
        $keyId = DB::table('translation_keys')
            ->where('namespace_id', $namespaceId)
            ->where('key', 'common.save')
            ->value('id');

        // A newer published release (v2) with a changed value becomes the active pack.
        $v2Values = ['common.save' => 'Lưu (v2)'];
        $v2Checksum = app(TranslationPackChecksum::class)->hash($v2Values);
        $v2Id = DB::table('translation_releases')->insertGetId([
            'namespace_id' => $namespaceId,
            'locale' => 'vi-VN',
            'version' => 'v2-test',
            'scope_type' => 'product',
            'scope_id' => '',
            'checksum' => $v2Checksum,
            'status' => 'published',
            'published_at' => now()->addMinute(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('translation_release_items')->insert([
            'release_id' => $v2Id,
            'translation_key_id' => $keyId,
            'value' => 'Lưu (v2)',
            'value_checksum' => hash('sha256', 'Lưu (v2)'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Cache::flush();

        $active = $this->getJson('/api/v1/localization/packs/x2.shared/vi-VN')->json('data');
        self::assertSame('v2-test', $active['version']);
        self::assertSame('Lưu (v2)', $active['values']['common.save']);

        // Rollback: mark v2 rolled_back (NOT deleted); the previous seed release is active again.
        DB::table('translation_releases')->where('id', $v2Id)->update(['status' => 'rolled_back']);
        Cache::flush();

        $reverted = $this->getJson('/api/v1/localization/packs/x2.shared/vi-VN')->json('data');
        self::assertSame('seed-2026.08.06', $reverted['version']);
        self::assertSame('Lưu', $reverted['values']['common.save']);

        // History is intact: the rolled-back release row still exists.
        $this->assertDatabaseHas('translation_releases', ['id' => $v2Id, 'status' => 'rolled_back']);
    }
}
