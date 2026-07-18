<?php

namespace App\Console\Commands;

use App\Models\Resident;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Moves legacy resident KYC images + legal documents from the PUBLIC disk to the
 * PRIVATE (local) disk, keeping the same relative path so existing records resolve
 * unchanged (the form + PrivateMediaController now read from disk('local')).
 *
 *   php artisan kyc:migrate-private --dry-run   # report only
 *   php artisan kyc:migrate-private             # copy public -> private
 *   php artisan kyc:migrate-private --delete    # also remove the public copy after verifying
 */
class MigrateKycToPrivate extends Command
{
    protected $signature = 'kyc:migrate-private {--dry-run : Chỉ báo cáo, không di chuyển} {--delete : Xoá bản public sau khi copy}';

    protected $description = 'Chuyển file KYC/tài liệu cư dân từ disk public sang private (local)';

    private const IMAGE_FIELDS = ['id_front_path', 'id_back_path', 'portrait_path'];

    public function handle(): int
    {
        $public = Storage::disk('public');
        $local = Storage::disk('local');
        $dry = (bool) $this->option('dry-run');
        $delete = (bool) $this->option('delete');

        $moved = 0;
        $missing = 0;
        $skipped = 0;

        Resident::withoutGlobalScopes()->chunkById(200, function ($residents) use ($public, $local, $dry, $delete, &$moved, &$missing, &$skipped) {
            foreach ($residents as $resident) {
                $paths = [];
                foreach (self::IMAGE_FIELDS as $f) {
                    if (! empty($resident->{$f})) {
                        $paths[] = $resident->{$f};
                    }
                }
                foreach ((array) ($resident->documents ?? []) as $doc) {
                    if (! empty($doc)) {
                        $paths[] = $doc;
                    }
                }

                foreach ($paths as $path) {
                    if ($local->exists($path)) {
                        $skipped++; // already private
                        continue;
                    }
                    if (! $public->exists($path)) {
                        $missing++;
                        $this->warn("  missing on public: resident#{$resident->id} {$path}");
                        continue;
                    }
                    if ($dry) {
                        $this->line("  would move: {$path}");
                        $moved++;
                        continue;
                    }

                    $local->writeStream($path, $public->readStream($path));
                    if ($local->exists($path) && $delete) {
                        $public->delete($path);
                    }
                    $moved++;
                }
            }
        });

        $this->info(($dry ? '[DRY-RUN] ' : '')."Xong. moved={$moved} skipped(private sẵn)={$skipped} missing={$missing}");

        return self::SUCCESS;
    }
}
