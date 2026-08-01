<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\CommunitySeed\CommunityMassSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Sinh dữ liệu cộng đồng ở nhiều quy mô để test UX / feed / phân trang / kiểm duyệt.
 *
 *   php artisan community:seed-scale --profile=demo --tenant=1 --project=1 --reset
 *   php artisan community:seed-scale --profile=ux   --tenant=1 --project=1 --resume
 *
 * ⚠ CHỈ chạy `demo`/`ux` trên DB dev. `load`/`full` (1 triệu bài, tới 25 triệu
 * comment) để DÀNH cho staging — xem docblock CommunityMassSeeder về việc comment
 * quy mô full cần bảng chuyên dụng `community_comments` của GĐ7 (chưa có).
 *
 * Chế độ mass seed TẮT observer/event/notification: dùng DB::table() (batch insert
 * thuần) + Model::withoutEvents() bọc toàn bộ để không kích hoạt model event nào.
 */
final class CommunitySeedScale extends Command
{
    protected $signature = 'community:seed-scale
        {--profile=demo : demo|ux|load|full}
        {--tenant=1 : Tenant ID}
        {--project=1 : Project ID}
        {--posts= : Ghi đè số bài}
        {--comments-min= : Ghi đè min comment/bài}
        {--comments-max= : Ghi đè max comment/bài}
        {--batch= : Ghi đè batch size}
        {--reset : Xoá bài do bộ seed sinh ra (theo seed_tag) trước khi seed}
        {--resume : Tiếp tục từ checkpoint}
        {--dry-run : Chỉ in kế hoạch}';

    protected $description = 'Seed dữ liệu cộng đồng X2-BMS ở quy mô demo/ux/load/full.';

    public function handle(CommunityMassSeeder $seeder): int
    {
        $profileName = (string) $this->option('profile');
        $profile = config("community_seed.profiles.$profileName");

        if (! is_array($profile)) {
            $this->error("Profile không hợp lệ: {$profileName}");

            return self::FAILURE;
        }

        foreach ([
            'posts' => 'posts',
            'comments_min' => 'comments-min',
            'comments_max' => 'comments-max',
            'batch_size' => 'batch',
        ] as $key => $option) {
            if ($this->option($option) !== null) {
                $profile[$key] = (int) $this->option($option);
            }
        }

        $tenantId = (int) $this->option('tenant');
        $projectId = (int) $this->option('project');

        $this->table(['Thiết lập', 'Giá trị'], [
            ['profile', $profileName],
            ['tenant', $tenantId],
            ['project', $projectId],
            ['posts', number_format((int) $profile['posts'])],
            ['comments/post', $profile['comments_min'].'–'.$profile['comments_max']],
            ['ước lượng comments', number_format((int) round($profile['posts'] * (($profile['comments_min'] + $profile['comments_max']) / 2)))],
        ]);

        if (in_array($profileName, ['load', 'full'], true)) {
            $this->warn('  ⚠ Profile "'.$profileName.'" sinh tới hàng triệu bài / chục triệu comment.');
            $this->warn('    KHÔNG chạy trên DB dev/laptop — dùng staging. Xem docs/dev/03_data_arch/community-mass-seed.md.');
        }

        if ($this->option('dry-run')) {
            $this->info('  Dry-run: không ghi dữ liệu.');

            return self::SUCCESS;
        }

        $started = microtime(true);

        try {
            // Bọc toàn bộ trong withoutEvents: không observer/notification/search-index
            // nào chạy trong chế độ mass seed.
            $totals = Model::withoutEvents(function () use ($seeder, $tenantId, $projectId, $profile) {
                if ($this->option('reset')) {
                    $removed = $seeder->reset($tenantId, $projectId);
                    $this->line("  Reset: đã xoá {$removed} bài seed cũ (giữ nguyên dữ liệu demo/thật).");
                }

                return $seeder->run(
                    tenantId: $tenantId,
                    projectId: $projectId,
                    profile: $profile,
                    resume: (bool) $this->option('resume'),
                    output: $this->output,
                );
            });
        } catch (Throwable $e) {
            $this->error('  Lỗi seed: '.$e->getMessage());

            return self::FAILURE;
        }

        $elapsed = round(microtime(true) - $started, 1);

        $this->table(['Kết quả', 'Số lượng'], [
            ['posts', number_format($totals['posts'])],
            ['comments', number_format($totals['comments'])],
            ['reactions', number_format($totals['reactions'])],
            ['reports', number_format($totals['reports'])],
            ['thời gian (s)', $elapsed],
        ]);
        $this->info('  Hoàn tất community mass seed.');

        return self::SUCCESS;
    }
}
