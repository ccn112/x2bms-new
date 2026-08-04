<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * ③ Cổng đọc (ADR-001 tenant-scope-discipline) — RATCHET chống sinh "cửa sau" mới.
 *
 * Mỗi lần BỎ TENANT scope trên đường phục vụ request (Http/Filament/Services) là một
 * chỗ có thể rò dữ liệu tenant khác nếu không re-scope tường minh. Không cấm tuyệt đối
 * (nhiều chỗ hợp lệ: chợ BĐS/cộng đồng vốn xuyên tenant, đọc theo ID sau khi đã auth),
 * nhưng KHÓA số lượng hiện tại: test fail nếu một file TĂNG số lần hoặc có FILE MỚI.
 *
 * Thêm mới = quyết định có chủ ý → cập nhật `tenant_scope_baseline.json` kèm lý do
 * trong PR (code review thấy được) + kèm re-scope tường minh. Mục tiêu: chỉ giảm dần.
 *
 * KHÔNG tính `withoutGlobalScopes([SoftDeletingScope::class])` (bỏ soft-delete, không
 * đụng tenant).
 */
class TenantScopeRatchetTest extends TestCase
{
    private const DIRS = ['app/Http', 'app/Filament', 'app/Services'];

    /** Chỉ bắt BỎ TENANT scope: bare withoutGlobalScopes() hoặc withoutGlobalScope('tenant'). */
    private const PATTERN = "/withoutGlobalScopes\(\)|withoutGlobalScope\('tenant'\)/";

    public function test_khong_sinh_them_cho_bo_tenant_scope_moi(): void
    {
        $baseline = json_decode(file_get_contents(base_path('tests/Architecture/tenant_scope_baseline.json')), true);
        unset($baseline['_doc']);

        $current = $this->scan();

        $violations = [];
        foreach ($current as $file => $count) {
            $allowed = $baseline[$file] ?? 0;
            if ($count > $allowed) {
                $violations[] = sprintf('  %s: %d (baseline %d) — +%d chỗ bỏ tenant scope MỚI', $file, $count, $allowed, $count - $allowed);
            }
        }

        $this->assertEmpty($violations, implode("\n", array_merge(
            ['Phát hiện chỗ BỎ TENANT scope mới trên đường request (ADR-001 ③).',
                'Nếu CÓ CHỦ Ý (cross-tenant hợp lệ + re-scope tường minh): cập nhật',
                'tests/Architecture/tenant_scope_baseline.json kèm lý do trong PR.', ''],
            $violations,
        )));
    }

    public function test_baseline_khong_thua_file_da_xoa(): void
    {
        // Giữ baseline sạch: file trong baseline mà nay = 0 → nên gỡ khỏi baseline.
        $baseline = json_decode(file_get_contents(base_path('tests/Architecture/tenant_scope_baseline.json')), true);
        unset($baseline['_doc']);
        $current = $this->scan();

        $stale = array_values(array_filter(array_keys($baseline), fn ($f) => ($current[$f] ?? 0) === 0));
        $this->assertEmpty($stale, "File đã hết bỏ tenant-scope, gỡ khỏi baseline (siết ratchet):\n  ".implode("\n  ", $stale));
    }

    /** @return array<string,int> file (path chuẩn '/') → số lần bỏ tenant scope. */
    private function scan(): array
    {
        $out = [];
        foreach (self::DIRS as $dir) {
            $base = base_path($dir);
            if (! is_dir($base)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $n = preg_match_all(self::PATTERN, file_get_contents($file->getPathname()));
                if ($n > 0) {
                    $rel = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
                    $out[$rel] = $n;
                }
            }
        }

        return $out;
    }
}
