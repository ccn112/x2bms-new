<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Services\Localization\PublishTranslationRelease;
use App\Services\Localization\TranslationPackChecksum;
use App\Services\Localization\TranslationValueWriter;
use Database\Seeders\LocalizationMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * I18N-010 — Translation Center publish/rollback service.
 *
 * Proves the SuperAdmin publish pipeline: editing a value + publishing a new release
 * surfaces the change to the resident app pack API with a cross-platform-identical
 * checksum, rollback re-points the active version without deleting history, and
 * published releases are immutable.
 */
final class PublishTranslationReleaseTest extends TestCase
{
    use RefreshDatabase;

    private const NS = 'x2.shared';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LocalizationMasterSeeder::class);
    }

    private function keyId(string $key): int
    {
        $namespaceId = DB::table('translation_namespaces')->where('code', self::NS)->value('id');

        return (int) DB::table('translation_keys')
            ->where('namespace_id', $namespaceId)
            ->where('key', $key)
            ->value('id');
    }

    public function test_publishing_after_editing_a_value_delivers_new_version_and_checksum(): void
    {
        // Baseline pack from the seed release.
        $before = $this->getJson('/api/v1/localization/packs/'.self::NS.'/vi-VN')->assertOk()->json('data');

        // Edit the product-scope value via the domain writer (what the Filament page calls).
        app(TranslationValueWriter::class)->writeProductValue($this->keyId('common.save'), 'vi-VN', 'Lưu lại (đã sửa)');

        // Editing alone must NOT change the delivered pack (still the old published release).
        Cache::flush();
        $stillOld = $this->getJson('/api/v1/localization/packs/'.self::NS.'/vi-VN')->json('data');
        self::assertSame($before['version'], $stillOld['version']);
        self::assertSame('Lưu', $stillOld['values']['common.save']);

        // Publish a NEW release -> the edit becomes live.
        $releaseId = app(PublishTranslationRelease::class)->publish(self::NS, 'vi-VN', 'rel-test-1');
        self::assertGreaterThan(0, $releaseId);

        $after = $this->getJson('/api/v1/localization/packs/'.self::NS.'/vi-VN')->assertOk()->json('data');
        self::assertSame('rel-test-1', $after['version']);
        self::assertSame('Lưu lại (đã sửa)', $after['values']['common.save']);
        self::assertNotSame($before['checksum'], $after['checksum']);

        // Checksum equals the canonical hash of the delivered values (Dart-verifier parity).
        $recomputed = app(TranslationPackChecksum::class)->hash($after['values']);
        self::assertSame($after['checksum'], $recomputed);
        self::assertSame($after['checksum'], DB::table('translation_releases')->where('id', $releaseId)->value('checksum'));

        // Audit row written.
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'translation.release.published',
            'subject_id' => $releaseId,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'translation.value.updated']);
    }

    public function test_rollback_reverts_to_prior_release_and_keeps_history(): void
    {
        app(TranslationValueWriter::class)->writeProductValue($this->keyId('common.save'), 'vi-VN', 'Lưu (v2)');
        $v2Id = app(PublishTranslationRelease::class)->publish(self::NS, 'vi-VN', 'rel-v2');

        $active = $this->getJson('/api/v1/localization/packs/'.self::NS.'/vi-VN')->json('data');
        self::assertSame('rel-v2', $active['version']);
        self::assertSame('Lưu (v2)', $active['values']['common.save']);

        // Rollback the active release.
        app(PublishTranslationRelease::class)->rollback($v2Id);

        $reverted = $this->getJson('/api/v1/localization/packs/'.self::NS.'/vi-VN')->json('data');
        self::assertSame('seed-2026.08.06', $reverted['version']);
        self::assertSame('Lưu', $reverted['values']['common.save']);

        // History intact: the rolled-back row still exists.
        $this->assertDatabaseHas('translation_releases', ['id' => $v2Id, 'status' => 'rolled_back']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'translation.release.rolled_back',
            'subject_id' => $v2Id,
        ]);
    }

    public function test_publishing_again_creates_new_version_and_does_not_mutate_old_release(): void
    {
        $firstId = app(PublishTranslationRelease::class)->publish(self::NS, 'vi-VN', 'rel-immutable-1');
        $firstRow = DB::table('translation_releases')->where('id', $firstId)->first();

        // Change a value and publish a second version.
        app(TranslationValueWriter::class)->writeProductValue($this->keyId('common.save'), 'vi-VN', 'Lưu (đổi lần 2)');
        $secondId = app(PublishTranslationRelease::class)->publish(self::NS, 'vi-VN', 'rel-immutable-2');

        self::assertNotSame($firstId, $secondId);

        // The old release row is byte-for-byte unchanged (immutable).
        $firstRowAfter = DB::table('translation_releases')->where('id', $firstId)->first();
        self::assertEquals($firstRow, $firstRowAfter);

        // Two distinct release rows coexist.
        self::assertSame(2, DB::table('translation_releases')
            ->whereIn('version', ['rel-immutable-1', 'rel-immutable-2'])
            ->where('namespace_id', DB::table('translation_namespaces')->where('code', self::NS)->value('id'))
            ->where('locale', 'vi-VN')
            ->count());
    }
}
