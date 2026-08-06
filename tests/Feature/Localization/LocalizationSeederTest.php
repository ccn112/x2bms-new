<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use Database\Seeders\LocalizationMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class LocalizationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_seeder_is_idempotent_and_has_expected_minimum_counts(): void
    {
        $this->seed(LocalizationMasterSeeder::class);
        $first = $this->counts();

        $this->seed(LocalizationMasterSeeder::class);
        $second = $this->counts();

        self::assertSame($first, $second);
        self::assertSame(6, $first['locales']);
        self::assertSame(9, $first['namespaces']);
        self::assertSame(340, $first['keys']);
        self::assertSame(680, $first['values']);
        self::assertSame(56, $first['glossary_terms']);
        self::assertSame(21, $first['notification_templates']);
        self::assertSame(18, $first['published_releases']);
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return [
            'locales' => DB::table('locales')->count(),
            'namespaces' => DB::table('translation_namespaces')->count(),
            'keys' => DB::table('translation_keys')->count(),
            'values' => DB::table('translation_values')->count(),
            'glossary_terms' => DB::table('translation_glossary_terms')->count(),
            'notification_templates' => DB::table('notification_templates')->count(),
            'published_releases' => DB::table('translation_releases')
                ->where('status', 'published')
                ->count(),
        ];
    }
}
