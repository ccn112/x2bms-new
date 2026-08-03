<?php

namespace Tests\Feature;

use App\Support\Api\ApiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Middleware `idempotency` chống double-submit: cùng Idempotency-Key ⇒ controller
 * chỉ chạy MỘT lần, lần sau phát lại response đã lưu. Key khác ⇒ chạy lại.
 */
class IdempotencyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function bindCountingRoute(): object
    {
        $counter = new class
        {
            public int $n = 0;
        };
        Route::post('/_test/idem', function () use ($counter) {
            $counter->n++;

            return ApiResponse::success(['n' => $counter->n]);
        })->middleware('idempotency');

        return $counter;
    }

    public function test_cung_key_thi_controller_chi_chay_mot_lan(): void
    {
        $counter = $this->bindCountingRoute();
        $key = 'idem-key-A';

        $r1 = $this->postJson('/_test/idem', [], ['Idempotency-Key' => $key]);
        $r2 = $this->postJson('/_test/idem', [], ['Idempotency-Key' => $key]);

        $r1->assertOk();
        $r2->assertOk();
        $this->assertSame(1, $counter->n, 'Controller phải chạy đúng một lần');
        $r2->assertHeader('Idempotent-Replay', 'true');
        $this->assertSame($r1->json('data.n'), $r2->json('data.n'));
    }

    public function test_key_khac_thi_chay_lai(): void
    {
        $counter = $this->bindCountingRoute();

        $this->postJson('/_test/idem', [], ['Idempotency-Key' => 'k1'])->assertOk();
        $this->postJson('/_test/idem', [], ['Idempotency-Key' => 'k2'])->assertOk();

        $this->assertSame(2, $counter->n);
    }

    public function test_khong_co_key_thi_khong_dedupe(): void
    {
        $counter = $this->bindCountingRoute();

        $this->postJson('/_test/idem')->assertOk();
        $this->postJson('/_test/idem')->assertOk();

        $this->assertSame(2, $counter->n);
    }

    public function test_key_tai_dung_voi_payload_khac_bi_tu_choi(): void
    {
        $this->bindCountingRoute();
        $key = 'idem-key-B';

        $this->postJson('/_test/idem', ['a' => 1], ['Idempotency-Key' => $key])->assertOk();
        $this->postJson('/_test/idem', ['a' => 2], ['Idempotency-Key' => $key])
            ->assertStatus(422);
    }
}
