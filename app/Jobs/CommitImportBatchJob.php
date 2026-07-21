<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Support\Import\ImportProfileRegistry;
use App\Support\Import\StagingImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Ghi (commit) 1 batch import ở NỀN qua queue — import bất đồng bộ.
 * Idempotent: `StagingImporter::commit()` chỉ xử lý dòng valid|warning, dòng đã
 * 'imported' được bỏ qua → gọi lại (retry) an toàn, không tạo trùng.
 */
class CommitImportBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * @param  array{tenant_id:int, building_id?:int|null, user_id?:int|null}  $context
     */
    public function __construct(
        public readonly int $batchId,
        public readonly array $context,
    ) {}

    public function handle(StagingImporter $importer): void
    {
        $batch = ImportBatch::find($this->batchId);
        if ($batch === null || $batch->status === 'committed') {
            return;
        }

        $batch->update(['status' => 'committing']);

        try {
            $importer->commit($batch, ImportProfileRegistry::for($batch->import_type), $this->context);
            // commit() tự đặt status committed/failed + committed_by/at.
        } catch (Throwable $e) {
            $batch->update(['status' => 'failed']);
            throw $e;
        }
    }
}
