<?php

declare(strict_types=1);

namespace App\Support\Import;

use Illuminate\Database\Eloquent\Model;

/**
 * Hợp đồng cho 1 loại import cụ thể (cư dân, dự án/nhân sự…) — phần nghiệp vụ mà
 * StagingImporter (engine dùng chung) gọi tới. Mỗi tầng/màn viết 1 profile.
 *
 * @property-read array<string,mixed> $context  Ngữ cảnh do caller truyền
 *   (tenant_id, building_id, user_id…). Truyền qua tham số $context các method.
 */
interface ImportProfile
{
    /** Giá trị `import_batches.import_type` (vd 'residents'). */
    public function importType(): string;

    /** Giá trị `import_batch_rows.row_type` (vd 'resident'). */
    public function rowType(): string;

    /** @return list<ImportColumnSpec> */
    public function columns(): array;

    /**
     * Rule nghiệp vụ NGOÀI validate field (vd trùng CCCD → cảnh báo). Trả list RowIssue.
     *
     * @param  array<string,mixed>  $normalized
     * @param  array<string,mixed>  $context
     * @return list<RowIssue>
     */
    public function validateRow(array $normalized, int $rowNumber, array $context): array;

    /**
     * Ghi 1 dòng hợp lệ vào hệ thống (đã bọc scope + audit trong profile).
     *
     * @param  array<string,mixed>  $normalized
     * @param  array<string,mixed>  $context
     */
    public function commitRow(array $normalized, array $context): Model;
}
