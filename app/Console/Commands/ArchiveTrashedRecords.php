<?php

namespace App\Console\Commands;

use App\Models\Apartment;
use App\Models\FeedbackRequest;
use App\Models\Resident;
use App\Models\VisitorRegistration;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Vòng đời xóa mềm → ARCHIVE: bản ghi xóa mềm quá N ngày được chụp snapshot bất biến
 * vào `archived_records` (bằng chứng + khôi phục thủ công). `--purge` mới xóa cứng bản
 * gốc, và CHỈ khi không còn con ràng buộc (tránh mồ côi/1451). KHÔNG đụng bảng TIỀN.
 */
class ArchiveTrashedRecords extends Command
{
    protected $signature = 'records:archive {--days=90 : Số ngày xóa mềm tối thiểu} {--purge : Xóa cứng bản gốc sau khi archive nếu an toàn}';

    protected $description = 'Archive (snapshot) các bản ghi đã xóa mềm quá hạn; tùy chọn purge bản gốc an toàn.';

    /** Model được phép archive (KHÔNG gồm bảng tiền). */
    private array $models = [
        Resident::class,
        Apartment::class,
        FeedbackRequest::class,
        VisitorRegistration::class,
    ];

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);
        $purge = (bool) $this->option('purge');
        $archived = 0;
        $purged = 0;

        foreach ($this->models as $modelClass) {
            /** @var class-string<Model> $modelClass */
            $modelClass::onlyTrashed()
                ->where('deleted_at', '<=', $cutoff)
                ->whereNotExists(function ($q) use ($modelClass) {
                    $q->select(DB::raw(1))->from('archived_records')
                        ->whereColumn('archived_records.model_id', (new $modelClass)->getTable().'.id')
                        ->where('archived_records.model_type', $modelClass);
                })
                ->chunkById(200, function ($rows) use ($modelClass, $purge, &$archived, &$purged) {
                    foreach ($rows as $record) {
                        DB::table('archived_records')->insert([
                            'tenant_id' => $record->tenant_id ?? null,
                            'model_type' => $modelClass,
                            'model_id' => $record->id,
                            'snapshot' => json_encode($record->getAttributes(), JSON_UNESCAPED_UNICODE),
                            'soft_deleted_at' => $record->deleted_at,
                            'archived_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $archived++;

                        // --purge: thử xóa cứng bản gốc; còn ràng buộc (FK 1451) → giữ
                        // nguyên xóa mềm, KHÔNG purge (tránh mồ côi).
                        if ($purge) {
                            try {
                                DB::transaction(fn () => $record->forceDelete());
                                DB::table('archived_records')
                                    ->where('model_type', $modelClass)->where('model_id', $record->id)
                                    ->update(['purged' => true]);
                                $purged++;
                            } catch (\Throwable) {
                                // còn con ràng buộc → giữ soft-deleted, chỉ archive snapshot.
                            }
                        }
                    }
                });
        }

        $this->info("Đã archive {$archived} bản ghi (>{$days} ngày xóa mềm)".($purge ? ", purge {$purged}." : '.'));

        return self::SUCCESS;
    }
}
