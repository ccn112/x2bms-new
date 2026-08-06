<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class LocalizationDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (App::environment('production')) {
            throw new RuntimeException('LocalizationDemoSeeder is forbidden in production.');
        }

        $tenantId = (int) env('X2_DEMO_TENANT_ID', 1);
        $projectId = (int) env('X2_DEMO_PROJECT_ID', 1);

        DB::table('tenant_locale_settings')->updateOrInsert(
            ['tenant_id' => $tenantId],
            [
                'default_locale' => 'vi-VN',
                'supported_locales' => json_encode(['vi-VN', 'en-US']),
                'allow_auto_translate' => true,
                'monthly_character_limit' => 500000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('project_locale_settings')->updateOrInsert(
            ['tenant_id' => $tenantId, 'project_id' => $projectId],
            [
                'default_locale' => 'vi-VN',
                'supported_locales' => json_encode(['vi-VN', 'en-US']),
                'allow_auto_translate' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $demoRows = $this->readJson('sample_content_translations.json');

        foreach ($demoRows as $row) {
            foreach ([
                'title' => $row['translated_title'] ?? null,
                'content' => $row['translated_content'] ?? null,
            ] as $field => $value) {
                if (!is_string($value) || $value === '') {
                    continue;
                }

                $sourceHash = hash('sha256', implode('|', [
                    $row['translatable_type'],
                    $row['translatable_id'],
                    $field,
                    $row['source_hash'] ?? 'demo',
                ]));

                $identity = [
                    'tenant_id' => $tenantId,
                    'project_id' => $projectId,
                    'translatable_type' => $row['translatable_type'],
                    'translatable_id' => $row['translatable_id'],
                    'field' => $field,
                    'target_locale' => $row['target_locale'],
                    'source_hash' => $sourceHash,
                ];
                $translationId = DB::table('content_translations')
                    ->where($identity)
                    ->value('id') ?? (string) Str::uuid();

                DB::table('content_translations')->updateOrInsert(
                    $identity,
                    [
                        'id' => $translationId,
                        'source_locale' => $row['source_locale'],
                        'translated_value' => $value,
                        'translation_method' => $row['translation_method'] === 'machine' ? 'ai' : 'manual',
                        'provider' => $row['translation_method'] === 'machine' ? 'demo-provider' : null,
                        'model' => $row['translation_method'] === 'machine' ? 'demo-model' : null,
                        'quality_score' => $row['translation_method'] === 'machine' ? 0.9000 : 1.0000,
                        'status' => $row['status'] === 'approved' ? 'published' : $row['status'],
                        'published_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        $residentEmail = (string) env('X2_DEMO_EN_RESIDENT_EMAIL', 'resident.en@example.com');
        $userId = DB::table('users')->where('email', $residentEmail)->value('id');

        if ($userId !== null) {
            DB::table('user_locale_preferences')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'locale' => 'en-US',
                    'follow_device' => false,
                    'auto_translate_content' => true,
                    'content_translation_preferences' => json_encode([
                        'community' => true,
                        'notifications' => true,
                        'support' => true,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJson(string $file): array
    {
        $path = database_path('seeders/data/localization/'.$file);

        if (!is_file($path)) {
            throw new RuntimeException("Missing localization demo seed file: {$path}");
        }

        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }
}
