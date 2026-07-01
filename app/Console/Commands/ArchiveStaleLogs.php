<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Move stale rows from bloat-prone log/audit tables into their `*_archive`
 * clone (see config/archive.php + migration 2026_07_01_000025). Idempotent and
 * batched so it is safe to run daily from the scheduler or by hand.
 */
class ArchiveStaleLogs extends Command
{
    protected $signature = 'logs:archive
                            {--table= : Only archive this one table}
                            {--dry-run : Report what would move without writing}';

    protected $description = 'Archive stale rows from log/audit tables into their *_archive clones';

    public function handle(): int
    {
        $retention = (array) config('archive.retention', []);
        $batch = max(100, (int) config('archive.batch_size', 2000));
        $only = $this->option('table');
        $dry = (bool) $this->option('dry-run');
        $grandTotal = 0;

        foreach ($retention as $table => $opts) {
            if ($only && $table !== $only) {
                continue;
            }

            $days = (int) ($opts['days'] ?? 0);
            $dateColumn = $opts['column'] ?? 'created_at';
            $archive = $table.'_archive';

            if ($days <= 0) {
                continue;
            }
            if (! Schema::hasTable($table) || ! Schema::hasTable($archive)) {
                $this->warn("skip {$table}: table or {$archive} missing");
                continue;
            }
            if (! Schema::hasColumn($table, $dateColumn)) {
                $this->warn("skip {$table}: no `{$dateColumn}` column");
                continue;
            }

            $cutoff = Carbon::now()->subDays($days)->toDateTimeString();
            $due = DB::table($table)->where($dateColumn, '<', $cutoff)->count();

            if ($due === 0) {
                $this->line("• {$table}: nothing older than {$days}d");
                continue;
            }

            if ($dry) {
                $this->line("• {$table}: would archive {$due} row(s) older than {$days}d (< {$cutoff})");
                continue;
            }

            $moved = $this->archiveTable($table, $archive, $dateColumn, $cutoff, $batch);
            $grandTotal += $moved;
            $this->info("• {$table}: archived {$moved} row(s) → {$archive}");
        }

        $this->newLine();
        $this->info($dry ? 'Dry run complete.' : "Done. {$grandTotal} row(s) archived.");

        return self::SUCCESS;
    }

    private function archiveTable(string $table, string $archive, string $dateColumn, string $cutoff, int $batch): int
    {
        $moved = 0;

        do {
            $ids = DB::table($table)
                ->where($dateColumn, '<', $cutoff)
                ->orderBy('id')
                ->limit($batch)
                ->pluck('id')
                ->all();

            if (empty($ids)) {
                break;
            }

            DB::transaction(function () use ($table, $archive, $ids) {
                // Column-safe copy: archive is `CREATE TABLE LIKE` → identical columns.
                DB::statement(
                    "INSERT INTO `{$archive}` SELECT * FROM `{$table}` WHERE `id` IN (".implode(',', $ids).')'
                );
                DB::table($table)->whereIn('id', $ids)->delete();
            });

            $moved += count($ids);
        } while (count($ids) === $batch);

        return $moved;
    }
}
