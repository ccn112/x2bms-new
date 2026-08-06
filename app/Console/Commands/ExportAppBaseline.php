<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Exports the current PUBLISHED product-scope translations to the resident app's bundled
 * baseline (assets/i18n/{locale}.json), a flat map of "namespace.key" => value per locale.
 *
 * This baseline is the app's offline source for context.tr(); the remote pack overrides it
 * at runtime. Regenerate whenever keys/values change so a fresh app build ships current
 * strings — the DB (Translation Center) stays the single source of truth.
 *
 *   php artisan i18n:export-app-baseline
 *   php artisan i18n:export-app-baseline --path=/abs/dir --locale=vi-VN --locale=en-US
 */
final class ExportAppBaseline extends Command
{
    protected $signature = 'i18n:export-app-baseline
        {--path= : Target dir (default: sibling x2mobile app assets/i18n)}
        {--locale=* : Locales to export (default: all enabled)}';

    protected $description = 'Export published translations to the resident app bundled baseline JSON';

    public function handle(): int
    {
        $dir = $this->option('path')
            ?: base_path('../x2mobile/apps/resident_mobile/assets/i18n');

        if (! is_dir($dir) && ! @mkdir($dir, 0777, true) && ! is_dir($dir)) {
            $this->error("Cannot create target dir: {$dir}");

            return self::FAILURE;
        }

        $locales = $this->option('locale') ?: DB::table('locales')
            ->where('enabled', true)
            ->orderBy('sort_order')
            ->pluck('code')
            ->all();

        foreach ($locales as $locale) {
            $rows = DB::table('translation_values as v')
                ->join('translation_keys as k', 'k.id', '=', 'v.translation_key_id')
                ->join('translation_namespaces as n', 'n.id', '=', 'k.namespace_id')
                ->where('v.locale', $locale)
                ->where('v.scope_type', 'product')
                ->where('v.scope_id', '')
                ->where('v.status', 'published')
                ->orderBy('n.code')
                ->orderBy('k.key')
                ->get(['n.code as namespace', 'k.key', 'v.value']);

            $map = [];
            foreach ($rows as $row) {
                $map[$row->namespace.'.'.$row->key] = (string) $row->value;
            }

            $file = rtrim($dir, '/\\').DIRECTORY_SEPARATOR.$locale.'.json';
            file_put_contents(
                $file,
                json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n",
            );

            $this->info(sprintf('%s → %d keys → %s', $locale, count($map), $file));
        }

        return self::SUCCESS;
    }
}
