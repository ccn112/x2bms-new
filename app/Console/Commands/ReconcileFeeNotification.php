<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Apartment;
use App\Models\BillingPeriod;
use App\Models\FeeType;
use App\Models\Statement;
use App\Models\StatementLine;
use App\Models\Tenant;
use App\Services\Billing\FeeNotificationCalculator;
use App\Support\Import\RowNormalizers as N;
use Illuminate\Console\Command;
use Spatie\SimpleExcel\SimpleExcelReader;

/**
 * AUDIT DI TRÚ — đối soát cấp DÒNG giữa file thông báo phí mẫu cũ và dữ liệu đã
 * import vào X2. Với mỗi dòng file: tính lại thành tiền (đúng cách hệ cũ) rồi tìm
 * `statement_lines` tương ứng (căn + kỳ + dịch vụ + kỳ dịch vụ) và so `amount`.
 *
 * Báo: khớp / lệch tiền / không tìm thấy dòng trong DB. KHÔNG sửa gì — chỉ audit.
 * Đây là oracle chứng minh độ trung thực khi chuyển phần mềm (số phải trùng tuyệt đối).
 */
class ReconcileFeeNotification extends Command
{
    protected $signature = 'billing:reconcile-fee-notification
                            {file : File .xlsx mẫu cũ đã import}
                            {--tenant=HPO-DEMO : Mã tenant demo đã import vào}
                            {--limit=0 : Chỉ in tối đa N dòng lệch (0 = tất cả)}';

    protected $description = 'Đối soát cấp dòng file thông báo phí ↔ statement_lines đã import (audit di trú)';

    public function handle(): int
    {
        $tenant = Tenant::where('code', $this->option('tenant'))->first();
        if (! $tenant) {
            $this->error("Không thấy tenant {$this->option('tenant')}.");

            return self::FAILURE;
        }

        $calc = new FeeNotificationCalculator;
        $limit = (int) $this->option('limit');

        $checked = 0;
        $matched = 0;
        $mismatched = 0;
        $notFound = 0;
        $fileTotal = '0';
        $printed = 0;

        foreach (SimpleExcelReader::create($this->argument('file'))->getRows() as $r) {
            $aptCode = trim((string) ($r['Mã căn hộ'] ?? ''));
            $svc = trim((string) ($r['Mã dịch vụ'] ?? ''));
            if ($aptCode === '' || $svc === '') {
                continue;
            }
            $checked++;

            $result = $calc->compute(
                (int) N::decimal((string) ($r['Loại giá áp dụng'] ?? '0')),
                (string) (N::decimal((string) ($r['Số lượng sử dụng'] ?? '0')) ?? '0'),
                (string) (N::money((string) ($r['Đơn giá cố định'] ?? '0')) ?: '0'),
                [
                    ['qty' => (string) (N::decimal((string) ($r['Định mức 1'] ?? '0')) ?? '0'), 'price' => (string) (N::money((string) ($r['Đơn giá 1'] ?? '0')) ?: '0')],
                    ['qty' => (string) (N::decimal((string) ($r['Định mức 2'] ?? '0')) ?? '0'), 'price' => (string) (N::money((string) ($r['Đơn giá 2'] ?? '0')) ?: '0')],
                    ['qty' => (string) (N::decimal((string) ($r['Định mức 3'] ?? '0')) ?? '0'), 'price' => (string) (N::money((string) ($r['Đơn giá 3'] ?? '0')) ?: '0')],
                ],
                (string) (N::money((string) ($r['Giảm giá'] ?? '0')) ?: '0'),
                (string) ($r['Chỉ số đầu'] ?? ''),
                (string) ($r['Chỉ số cuối'] ?? ''),
            );
            $expected = $result['amount'];
            $fileTotal = bcadd($fileTotal, (string) $expected, 0);

            $serviceStart = N::date((string) ($r['Ngày bắt đầu tính phí'] ?? ''));

            $line = StatementLine::query()
                ->where('fee_type_id', function ($q) use ($tenant, $svc) {
                    $q->select('id')->from('fee_types')->where('tenant_id', $tenant->id)->where('code', $svc)->limit(1);
                })
                ->where('service_period_start', $serviceStart)
                ->whereHas('statement', function ($q) use ($tenant, $aptCode) {
                    $q->where('tenant_id', $tenant->id)
                        ->whereHas('apartment', fn ($a) => $a->where('code', $aptCode));
                })
                ->first();

            if (! $line) {
                $notFound++;
                if ($limit === 0 || $printed < $limit) {
                    $this->line("  ✗ Không thấy DB: căn {$aptCode} · {$svc} · từ {$serviceStart} (file={$expected})");
                    $printed++;
                }

                continue;
            }

            if (bccomp((string) $line->amount, (string) $expected, 2) === 0) {
                $matched++;
            } else {
                $mismatched++;
                if ($limit === 0 || $printed < $limit) {
                    $this->line("  ✗ LỆCH: căn {$aptCode} · {$svc}: DB={$line->amount} vs file={$expected}");
                    $printed++;
                }
            }
        }

        $dbTotal = StatementLine::whereHas('statement', fn ($q) => $q->where('tenant_id', $tenant->id))->sum('amount');

        $this->newLine();
        $this->info('=== KẾT QUẢ ĐỐI SOÁT CẤP DÒNG ===');
        $this->line("  Kiểm: {$checked} · Khớp: {$matched} · Lệch: {$mismatched} · Không thấy: {$notFound}");
        $this->line('  Tổng theo FILE  = '.number_format((float) $fileTotal).' đ');
        $this->line('  Tổng trong DB   = '.number_format((float) $dbTotal).' đ');
        $this->line('  Chênh lệch tổng = '.number_format((float) bcsub((string) $dbTotal, $fileTotal, 0)).' đ');

        return $mismatched === 0 && $notFound === 0 ? self::SUCCESS : self::FAILURE;
    }
}
