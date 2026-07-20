<?php

declare(strict_types=1);

namespace App\Support\Import;

/**
 * Bộ đếm kết quả import + tập RowIssue — nền dùng chung cho cả import qua UI (staging)
 * lẫn import qua CLI. Ánh xạ trực tiếp sang các cột đếm của bảng `import_batches`
 * (total_rows/valid_rows/error_rows) khi commit.
 *
 * Port từ ImportSummary của x1web, gộp thêm danh sách issue.
 */
final class ImportSummary
{
    public int $processed = 0;

    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $warnings = 0;

    public int $errors = 0;

    /** @var list<RowIssue> */
    public array $issues = [];

    public function addIssue(RowIssue $issue): void
    {
        $this->issues[] = $issue;

        if ($issue->isError()) {
            $this->errors++;
        } else {
            $this->warnings++;
        }
    }

    public function markProcessed(): void
    {
        $this->processed++;
    }

    public function markCreated(): void
    {
        $this->created++;
    }

    public function markUpdated(): void
    {
        $this->updated++;
    }

    public function markSkipped(): void
    {
        $this->skipped++;
    }

    public function hasErrors(): bool
    {
        return $this->errors > 0;
    }

    /** Số dòng hợp lệ = đã xử lý trừ số dòng bỏ qua do lỗi. */
    public function validRows(): int
    {
        return max(0, $this->processed - $this->skipped);
    }

    /** @return array<string, int> */
    public function counters(): array
    {
        return [
            'processed' => $this->processed,
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'warnings' => $this->warnings,
            'errors' => $this->errors,
        ];
    }
}
