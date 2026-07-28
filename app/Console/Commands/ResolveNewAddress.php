<?php

namespace App\Console\Commands;

use App\Models\PublicProject;
use App\Services\Address\AddressResolver;
use Illuminate\Console\Command;

/**
 * Suy diễn ĐỊA CHỈ MỚI 2025 cho public_projects và ghi vào metadata_json.
 * KHÔNG sửa cột địa chỉ gốc (ward/district/province/address). Idempotent.
 *
 *   php artisan projects:resolve-new-address --all
 *   php artisan projects:resolve-new-address --limit=30
 */
class ResolveNewAddress extends Command
{
    protected $signature = 'projects:resolve-new-address {--all : Xử lý toàn bộ} {--limit=0 : Giới hạn số bản ghi}';

    protected $description = 'Suy diễn địa chỉ mới 2025 (34 tỉnh) và ghi vào public_projects.metadata_json';

    public function handle(AddressResolver $resolver): int
    {
        $limit = (int) $this->option('limit');

        $query = PublicProject::query()
            ->where(function ($q) {
                $q->whereNotNull('province')
                    ->orWhereNotNull('district')
                    ->orWhereNotNull('ward');
            })
            ->orderBy('id');

        if (! $this->option('all') && $limit > 0) {
            $query->limit($limit);
        }

        $counts = ['high' => 0, 'medium' => 0, 'low' => 0];
        $processed = 0;

        $query->chunkById(200, function ($projects) use ($resolver, &$counts, &$processed) {
            foreach ($projects as $p) {
                $res = $resolver->resolveNew($p->ward, $p->district, $p->province);

                $fullNew = trim(implode(', ', array_filter([
                    $res['ward_new'],
                    $res['province_new'],
                ])));

                $meta = $p->metadata_json ?? [];
                $meta['address_new'] = [
                    'province_new' => $res['province_new'],
                    'ward_new' => $res['ward_new'],
                    'full_new' => $fullNew !== '' ? $fullNew : null,
                    'matched_by' => $res['matched_by'],
                ];
                $meta['address_new_confidence'] = $res['confidence'];

                $p->metadata_json = $meta;
                $p->saveQuietly();

                $counts[$res['confidence']] = ($counts[$res['confidence']] ?? 0) + 1;
                $processed++;
            }
        });

        $this->info("Đã xử lý: {$processed} dự án");
        $this->table(
            ['confidence', 'count'],
            [
                ['high', $counts['high']],
                ['medium', $counts['medium']],
                ['low', $counts['low']],
            ]
        );

        return self::SUCCESS;
    }
}
