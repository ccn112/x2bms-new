<?php

declare(strict_types=1);

namespace App\Support\Import;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * Engine staging import DÙNG CHUNG 3 tầng (SA/HQ/BQL), độc lập nghiệp vụ.
 *
 * Luồng 6 bước của plan ánh xạ vào 2 giai đoạn:
 *   upload → (parse) → mapped → (normalize+validate) = stage()  → preview (đọc rows)
 *   → commit()  = ghi thật.
 *
 * Nghiệp vụ (cột, rule, ghi record, audit) nằm trong ImportProfile — engine chỉ điều
 * phối, đọc file (spatie/simple-excel), lưu staging vào import_batches/import_batch_rows,
 * đếm kết quả. Không tự biết tenant/building: nhận qua $context.
 */
class StagingImporter
{
    /**
     * Đọc file → tạo batch + rows đã normalize & validate (chưa ghi vào bảng đích).
     *
     * @param  array{tenant_id:int, building_id?:int|null, user_id?:int|null}  $context
     */
    public function stage(string $filePath, string $originalName, ImportProfile $profile, array $context, ?string $storagePath = null): ImportBatch
    {
        $columns = $profile->columns();
        $rules = $this->rulesFromColumns($columns);

        return DB::transaction(function () use ($filePath, $originalName, $storagePath, $profile, $context, $columns, $rules): ImportBatch {
            $batch = ImportBatch::create([
                'tenant_id' => $context['tenant_id'],
                'building_id' => $context['building_id'] ?? null,
                'import_type' => $profile->importType(),
                'file_name' => $originalName,
                'storage_path' => $storagePath,
                'status' => 'uploaded',
                'created_by' => $context['user_id'] ?? null,
            ]);

            $total = 0;
            $valid = 0;
            $errors = 0;
            $rowNumber = 1; // dòng 1 = header

            foreach (SimpleExcelReader::create($filePath)->getRows() as $raw) {
                $rowNumber++;
                $total++;

                /** @var array<string,mixed> $raw */
                $normalized = [];
                foreach ($columns as $col) {
                    $normalized[$col->key] = $col->extract($raw);
                }

                $issues = $this->validateFields($normalized, $rules, $rowNumber);
                $issues = array_merge($issues, $profile->validateRow($normalized, $rowNumber, $context));

                $status = $this->statusFor($issues);
                $status === 'error' ? $errors++ : $valid++;

                ImportBatchRow::create([
                    'tenant_id' => $context['tenant_id'],
                    'import_batch_id' => $batch->id,
                    'row_number' => $rowNumber,
                    'row_type' => $profile->rowType(),
                    'external_code' => $normalized['code'] ?? null,
                    'raw_payload' => $raw,
                    'normalized_payload' => $normalized,
                    'validation_status' => $status,
                    'validation_errors' => $issues === [] ? null : array_map(fn (RowIssue $i) => $i->toArray(), $issues),
                ]);
            }

            $batch->update([
                'total_rows' => $total,
                'valid_rows' => $valid,
                'error_rows' => $errors,
                'status' => 'validated',
            ]);

            return $batch;
        });
    }

    /**
     * Ghi các dòng hợp lệ (valid|warning) vào bảng đích qua profile->commitRow.
     * Dòng lỗi bị bỏ qua. Idempotent theo staging: dòng đã 'imported' không ghi lại.
     *
     * @param  array{tenant_id:int, building_id?:int|null, user_id?:int|null}  $context
     */
    public function commit(ImportBatch $batch, ImportProfile $profile, array $context): ImportSummary
    {
        $summary = new ImportSummary;

        DB::transaction(function () use ($batch, $profile, $context, $summary): void {
            $rows = $batch->rows()
                ->whereIn('validation_status', ['valid', 'warning'])
                ->orderBy('row_number')
                ->get();

            foreach ($rows as $row) {
                $summary->markProcessed();

                try {
                    $model = $profile->commitRow($row->normalized_payload ?? [], $context);
                    $row->update([
                        'validation_status' => 'imported',
                        'committed_entity_type' => $model::class,
                        'committed_entity_id' => $model->getKey(),
                    ]);
                    $summary->markCreated();
                } catch (\Throwable $e) {
                    $summary->markSkipped();
                    $summary->addIssue(RowIssue::error($row->row_number, $e->getMessage()));
                    $row->update([
                        'validation_status' => 'error',
                        'validation_errors' => array_merge($row->validation_errors ?? [], [
                            RowIssue::error($row->row_number, $e->getMessage())->toArray(),
                        ]),
                    ]);
                }
            }

            $batch->update([
                'status' => $summary->hasErrors() && $summary->created === 0 ? 'failed' : 'committed',
                'committed_by' => $context['user_id'] ?? null,
                'committed_at' => now(),
            ]);
        });

        return $summary;
    }

    /**
     * @param  list<ImportColumnSpec>  $columns
     * @return array<string, list<string>>
     */
    private function rulesFromColumns(array $columns): array
    {
        $rules = [];
        foreach ($columns as $col) {
            $set = $col->rules;
            if ($col->required) {
                array_unshift($set, 'required');
            } elseif ($set !== []) {
                array_unshift($set, 'nullable');
            }
            if ($set !== []) {
                $rules[$col->key] = $set;
            }
        }

        return $rules;
    }

    /**
     * @param  array<string,mixed>  $normalized
     * @param  array<string, list<string>>  $rules
     * @return list<RowIssue>
     */
    private function validateFields(array $normalized, array $rules, int $rowNumber): array
    {
        if ($rules === []) {
            return [];
        }

        $validator = Validator::make($normalized, $rules);

        if ($validator->passes()) {
            return [];
        }

        $issues = [];
        foreach ($validator->errors()->all() as $message) {
            $issues[] = RowIssue::error($rowNumber, $message);
        }

        return $issues;
    }

    /** @param  list<RowIssue>  $issues */
    private function statusFor(array $issues): string
    {
        foreach ($issues as $issue) {
            if ($issue->isError()) {
                return 'error';
            }
        }

        return $issues === [] ? 'valid' : 'warning';
    }
}
