<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Localization\TranslationPackChecksum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class LocalizationMasterSeeder extends Seeder
{
    private string $dataPath;

    public function run(): void
    {
        $this->dataPath = database_path('seeders/data/localization');

        DB::transaction(function (): void {
            $this->seedLocales();
            $this->seedNamespaces();
            $this->seedKeysAndValues();
            $this->seedGlossary();
            $this->seedNotificationTemplates();
            $this->publishSeedReleases();
        });
    }

    private function seedLocales(): void
    {
        foreach ($this->readJson('locales.json') as $row) {
            DB::table('locales')->updateOrInsert(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'native_name' => $row['native_name'],
                    'direction' => $row['direction'] ?? 'ltr',
                    'enabled' => (bool) ($row['enabled'] ?? false),
                    'is_default' => (bool) ($row['is_default'] ?? false),
                    'fallback_locale' => $row['fallback_locale'] ?? null,
                    'sort_order' => (int) ($row['sort_order'] ?? 100),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedNamespaces(): void
    {
        foreach ($this->readJson('translation_namespaces.json') as $row) {
            DB::table('translation_namespaces')->updateOrInsert(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'] ?? null,
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedKeysAndValues(): void
    {
        $namespaceIds = DB::table('translation_namespaces')->pluck('id', 'code');
        $rows = $this->readCsv('ui_translation_keys.csv');

        foreach ($rows as $row) {
            $namespaceId = $namespaceIds[$row['namespace']] ?? null;

            if ($namespaceId === null) {
                throw new RuntimeException("Unknown namespace: {$row['namespace']}");
            }

            $locked = $this->toBool($row['locked'] ?? false);

            $category = $this->nullable($row['category'] ?? null);

            DB::table('translation_keys')->updateOrInsert(
                [
                    'namespace_id' => $namespaceId,
                    'key' => $row['key'],
                ],
                [
                    'category' => $category,
                    'kind' => \App\Services\Localization\TranslationKeyKind::classify($category, $row['key']),
                    'description' => $this->nullable($row['description'] ?? null),
                    'placeholders' => json_encode($this->extractPlaceholders(
                        ($row['vi-VN'] ?? '').' '.($row['en-US'] ?? '')
                    )),
                    'allow_tenant_override' => !$locked,
                    'is_critical' => $locked,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $translationKeyId = DB::table('translation_keys')
                ->where('namespace_id', $namespaceId)
                ->where('key', $row['key'])
                ->value('id');

            foreach (['vi-VN', 'en-US'] as $locale) {
                $value = (string) ($row[$locale] ?? '');

                if ($value === '') {
                    continue;
                }

                DB::table('translation_values')->updateOrInsert(
                    [
                        'translation_key_id' => $translationKeyId,
                        'locale' => $locale,
                        'scope_type' => 'product',
                        'scope_id' => '',
                    ],
                    [
                        'value' => $value,
                        'status' => 'published',
                        'translation_method' => 'import',
                        'source_hash' => hash('sha256', $value),
                        'published_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }
    }

    private function seedGlossary(): void
    {
        DB::table('translation_glossaries')->updateOrInsert(
            ['code' => 'x2-bms-core', 'scope_type' => 'product', 'scope_id' => ''],
            [
                'name' => 'X2-BMS Core Glossary',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $glossaryId = DB::table('translation_glossaries')
            ->where('code', 'x2-bms-core')
            ->where('scope_type', 'product')
            ->where('scope_id', '')
            ->value('id');

        foreach ($this->readCsv('glossary_vi_en.csv') as $row) {
            DB::table('translation_glossary_terms')->updateOrInsert(
                [
                    'glossary_id' => $glossaryId,
                    'source_locale' => $row['source_locale'],
                    'target_locale' => $row['target_locale'],
                    'source_term' => $row['source_term'],
                ],
                [
                    'target_term' => $row['target_term'],
                    'notes' => $this->nullable($row['note'] ?? null),
                    'case_sensitive' => false,
                    'locked' => $this->toBool($row['locked'] ?? false),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedNotificationTemplates(): void
    {
        foreach ($this->readJson('notification_templates.json') as $template) {
            DB::table('notification_templates')->updateOrInsert(
                ['code' => $template['code']],
                [
                    'category' => $template['category'],
                    'risk' => $this->normalizeRisk((string) ($template['risk'] ?? 'medium')),
                    'allowed_variables' => json_encode($template['variables'] ?? []),
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $templateId = DB::table('notification_templates')
                ->where('code', $template['code'])
                ->value('id');

            DB::table('notification_template_versions')->updateOrInsert(
                ['template_id' => $templateId, 'version' => 1],
                [
                    'status' => 'published',
                    'published_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $versionId = DB::table('notification_template_versions')
                ->where('template_id', $templateId)
                ->where('version', 1)
                ->value('id');

            foreach (($template['channels'] ?? []) as $channel => $localizedRows) {
                foreach ($localizedRows as $locale => $content) {
                    $body = (string) ($content['body'] ?? '');

                    DB::table('notification_template_localizations')->updateOrInsert(
                        [
                            'template_version_id' => $versionId,
                            'channel' => $channel,
                            'locale' => $locale,
                        ],
                        [
                            'title' => $content['title'] ?? null,
                            'body' => $body,
                            'channel_options' => null,
                            'body_checksum' => hash('sha256', ($content['title'] ?? '')."\n".$body),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    );
                }
            }
        }
    }

    private function publishSeedReleases(): void
    {
        $version = 'seed-2026.08.06';
        $namespaces = DB::table('translation_namespaces')->get(['id', 'code']);

        foreach ($namespaces as $namespace) {
            foreach (['vi-VN', 'en-US'] as $locale) {
                $values = DB::table('translation_values as values')
                    ->join('translation_keys as keys', 'keys.id', '=', 'values.translation_key_id')
                    ->where('keys.namespace_id', $namespace->id)
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

                $canonicalValues = $values
                    ->mapWithKeys(fn ($row): array => [(string) $row->key => (string) $row->value])
                    ->all();
                $checksum = app(TranslationPackChecksum::class)->hash($canonicalValues);

                DB::table('translation_releases')->updateOrInsert(
                    [
                        'namespace_id' => $namespace->id,
                        'locale' => $locale,
                        'version' => $version,
                        'scope_type' => 'product',
                        'scope_id' => '',
                    ],
                    [
                        'checksum' => $checksum,
                        'status' => 'published',
                        'published_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );

                $releaseId = DB::table('translation_releases')
                    ->where('namespace_id', $namespace->id)
                    ->where('locale', $locale)
                    ->where('version', $version)
                    ->where('scope_type', 'product')
                    ->where('scope_id', '')
                    ->value('id');

                DB::table('translation_release_items')->where('release_id', $releaseId)->delete();

                foreach ($values as $value) {
                    DB::table('translation_release_items')->insert([
                        'release_id' => $releaseId,
                        'translation_key_id' => $value->translation_key_id,
                        'value' => $value->value,
                        'value_checksum' => hash('sha256', (string) $value->value),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJson(string $file): array
    {
        $path = $this->dataPath.DIRECTORY_SEPARATOR.$file;

        if (!is_file($path)) {
            throw new RuntimeException("Missing localization seed file: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readCsv(string $file): array
    {
        $path = $this->dataPath.DIRECTORY_SEPARATOR.$file;
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Cannot open localization seed file: {$path}");
        }

        $header = fgetcsv($handle);

        if (!is_array($header)) {
            fclose($handle);
            throw new RuntimeException("Invalid CSV header: {$path}");
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? (string) $header[0];
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null] || $data === []) {
                continue;
            }

            $data = array_pad($data, count($header), '');
            $rows[] = array_combine($header, array_slice($data, 0, count($header)));
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function extractPlaceholders(string $value): array
    {
        preg_match_all('/\{\{([a-zA-Z0-9_.-]+)\}\}/', $value, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    private function normalizeRisk(string $risk): string
    {
        return match (strtolower($risk)) {
            'low', 'medium', 'high', 'critical' => strtolower($risk),
            default => 'medium',
        };
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
