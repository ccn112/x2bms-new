<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\FeeType;
use App\Models\Project;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Support\Import\Profiles\FeeNotificationImportProfile;
use App\Support\Import\StagingImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * DEMO / DI TRÚ — nạp file "thông báo phí" mẫu CŨ vào một tenant demo để kiểm tra
 * end-to-end (Option A) + đối soát tổng tiền với phần mềm cũ.
 *
 * Dựng scaffolding tối thiểu từ chính file (tenant demo, dự án, toà, kỳ phí, 5 loại
 * phí, các căn hộ theo mã trong file) rồi stage + commit qua khung import chung.
 * Idempotent: scaffolding firstOrCreate, import theo natural key.
 *
 * KHÔNG chạy trên production trừ khi có --force.
 */
class ImportFeeNotificationDemo extends Command
{
    protected $signature = 'billing:import-fee-notification-demo
                            {file : Đường dẫn file .xlsx mẫu cũ}
                            {--commit : Ghi thật (mặc định chỉ stage + báo)}
                            {--force : Cho phép chạy trên production}';

    protected $description = 'Nạp file thông báo phí mẫu cũ (HPO) vào tenant demo + đối soát tổng (P2/di trú)';

    private const FEE_TYPES = [
        ['PQL', 'Phí quản lý', 'management'],
        ['NUOC', 'Phí nước', 'utility'],
        ['XEMAY', 'Phí xe máy', 'parking'],
        ['XEDAPDIEN', 'Phí xe đạp điện', 'parking'],
        ['XEDAP', 'Phí xe đạp', 'parking'],
    ];

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Chặn chạy trên production. Thêm --force nếu chắc chắn.');

            return self::FAILURE;
        }

        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("Không thấy file: {$file}");

            return self::FAILURE;
        }

        // 1) Quét file: mã căn hộ distinct + kỳ + tổng tiền tham chiếu (theo cách hệ cũ tính đã port).
        $this->info('Đang quét file…');
        $apartmentCodes = [];
        $periodCode = null;
        $rowCount = 0;
        foreach (SimpleExcelReader::create($file)->getRows() as $r) {
            $code = trim((string) ($r['Mã căn hộ'] ?? ''));
            if ($code === '') {
                continue;
            }
            $apartmentCodes[$code] = true;
            $periodCode ??= trim((string) ($r['Kỳ'] ?? ''));
            $rowCount++;
        }
        $apartmentCodes = array_keys($apartmentCodes);
        $this->line("  Dòng: {$rowCount} · Căn hộ distinct: ".count($apartmentCodes)." · Kỳ: {$periodCode}");

        // 2) Scaffolding demo.
        $scaffold = DB::transaction(function () use ($apartmentCodes, $periodCode) {
            $tenant = Tenant::firstOrCreate(['code' => 'HPO-DEMO'], ['name' => 'HPO Demo (di trú)']);
            $project = Project::firstOrCreate(['tenant_id' => $tenant->id, 'code' => 'PRJ-HPO-DEMO'], ['name' => 'Happy One Demo']);
            $building = Building::firstOrCreate(['tenant_id' => $tenant->id, 'project_id' => $project->id, 'code' => 'BLD-HPO-DEMO'], ['name' => 'Toà HPO']);
            BillingPeriod::firstOrCreate(
                ['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => $periodCode],
                ['label' => 'Kỳ '.$periodCode, 'period_month' => substr($periodCode, 0, 4).'-'.substr($periodCode, 4, 2).'-01', 'due_date' => substr($periodCode, 0, 4).'-'.substr($periodCode, 4, 2).'-25'],
            );
            foreach (self::FEE_TYPES as [$code, $name, $cat]) {
                FeeType::firstOrCreate(['tenant_id' => $tenant->id, 'code' => $code], ['name' => $name, 'category' => $cat, 'is_critical' => false, 'payment_priority' => 100]);
            }
            foreach ($apartmentCodes as $code) {
                Apartment::firstOrCreate(['tenant_id' => $tenant->id, 'building_id' => $building->id, 'code' => $code]);
            }

            return ['tenant' => $tenant, 'building' => $building];
        });

        $ctx = ['tenant_id' => $scaffold['tenant']->id, 'building_id' => $scaffold['building']->id, 'user_id' => null];

        // 3) Stage.
        $this->info('Đang stage (parse + validate)…');
        $profile = new FeeNotificationImportProfile;
        $importer = new StagingImporter;
        $batch = $importer->stage($file, basename($file), $profile, $ctx);
        $this->line("  Batch #{$batch->id}: tổng {$batch->total_rows} · hợp lệ {$batch->valid_rows} · lỗi {$batch->error_rows}");

        if (! $this->option('commit')) {
            $this->warn('Chưa --commit → chỉ stage. Thêm --commit để ghi thật.');

            return self::SUCCESS;
        }

        // 4) Commit.
        $this->info('Đang commit (ghi statement_lines)…');
        $summary = $importer->commit($batch->fresh(), $profile, $ctx);
        $this->line("  Đã ghi: {$summary->created} · bỏ qua: {$summary->skipped}");

        // 5) Đối soát.
        $tenantId = $scaffold['tenant']->id;
        $stmtCount = Statement::where('tenant_id', $tenantId)->count();
        $total = StatementLine::whereHas('statement', fn ($q) => $q->where('tenant_id', $tenantId))->sum('amount');
        $this->newLine();
        $this->info('=== ĐỐI SOÁT ===');
        $this->line("  Bảng kê: {$stmtCount}");
        $byFamily = StatementLine::whereHas('statement', fn ($q) => $q->where('tenant_id', $tenantId))
            ->select('fee_category', DB::raw('SUM(amount) s'), DB::raw('COUNT(*) c'))
            ->groupBy('fee_category')->get();
        foreach ($byFamily as $f) {
            $this->line(sprintf('  %-12s = %15s đ (%d dòng)', $f->fee_category, number_format((float) $f->s), $f->c));
        }
        $this->info('  TỔNG = '.number_format((float) $total).' đ');

        return self::SUCCESS;
    }
}
