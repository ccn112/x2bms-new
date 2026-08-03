<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Import\ImportProfileRegistry;
use Illuminate\Console\Command;
use Spatie\SimpleExcel\SimpleExcelWriter;

/**
 * Sinh FILE MẪU import cho kế toán, tự dựng từ `columns()` của profile (header +
 * 1 dòng ví dụ) — luôn khớp code, không lệch tài liệu.
 *
 * Hai lựa chọn (2 cách nhập cho kế toán):
 *   - `fee_notification` — MẪU CŨ: giữ nguyên file phần mềm cũ (24 cột, hệ tự tính).
 *   - `billing_charges`  — MẪU MỚI (canonical): kế toán nhập "Thành tiền" đã chốt +
 *     tài sản rõ ràng, gọn hơn, tối ưu cho vận hành X2.
 */
class MakeImportTemplate extends Command
{
    protected $signature = 'billing:make-import-template
                            {type : fee_notification | billing_charges}
                            {--out= : Đường dẫn file .xlsx xuất ra}';

    protected $description = 'Sinh file .xlsx mẫu import (mẫu cũ / mẫu mới) từ khai báo cột của profile';

    public function handle(): int
    {
        $type = $this->argument('type');
        try {
            $profile = ImportProfileRegistry::for($type);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $out = $this->option('out') ?: storage_path("app/templates/import_template_{$type}.xlsx");
        @mkdir(dirname($out), 0777, true);

        $header = [];
        $example = [];
        foreach ($profile->columns() as $col) {
            $header[] = $col->label;
            $example[$col->label] = $col->example ?? '';
        }

        $writer = SimpleExcelWriter::create($out);
        $writer->addRow($example); // header lấy từ keys + 1 dòng ví dụ
        $writer->close();

        $this->info("Đã sinh mẫu \"{$type}\" ({$this->countRequired($profile)} cột bắt buộc / ".count($header).' cột): '.$out);

        return self::SUCCESS;
    }

    private function countRequired($profile): int
    {
        return count(array_filter($profile->columns(), fn ($c) => $c->required));
    }
}
