<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chống double-submit cho POST tài chính. Client gửi header `Idempotency-Key`
 * (một key cho một thao tác, giữ nguyên qua retry). Middleware:
 *
 *  1. Không có key hoặc không phải POST → cho qua (không ép buộc để không phá
 *     client cũ; các slice mới đều gửi key).
 *  2. Key đã có + đã có response lưu → PHÁT LẠI response cũ (không chạy lần hai).
 *  3. Key đã có nhưng cùng payload đang chạy (chưa có response) → 409 đang xử lý.
 *  4. Key đã có nhưng payload KHÁC → 422 (tái dùng key cho request khác).
 *  5. Key mới → chèn hàng khóa, chạy controller, lưu lại response (<500) rồi trả.
 *     Response >=500 thì xóa khóa để client được phép thử lại.
 */
class EnsureIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->header('Idempotency-Key', ''));
        if ($key === '' || ! $request->isMethod('post')) {
            return $next($request);
        }

        $userId = $request->user()?->id;
        $scope = sha1(($userId ?? 'guest').'|'.$request->method().'|'.$request->path());
        $hash = sha1($request->getContent() ?: '');

        $existing = IdempotencyKey::where('idempotency_key', $key)
            ->where('scope', $scope)
            ->first();

        if ($existing) {
            if ($existing->request_hash !== null && $existing->request_hash !== $hash) {
                return ApiResponse::error(
                    'IDEMPOTENCY_KEY_REUSED',
                    __('Idempotency-Key đã dùng cho một yêu cầu khác.'),
                    422,
                );
            }
            if ($existing->response_status !== null) {
                return response(
                    $existing->response_body ?? '',
                    $existing->response_status,
                )->header('Content-Type', 'application/json')
                    ->header('Idempotent-Replay', 'true');
            }

            return ApiResponse::error(
                'IDEMPOTENCY_IN_PROGRESS',
                __('Yêu cầu đang được xử lý, vui lòng chờ.'),
                409,
                retryable: true,
            );
        }

        try {
            $record = IdempotencyKey::create([
                'idempotency_key' => $key,
                'scope' => $scope,
                'user_id' => $userId,
                'method' => $request->method(),
                'path' => $request->path(),
                'request_hash' => $hash,
                'locked_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Đua chèn: request song song cùng key vừa thắng → coi như đang xử lý.
            return ApiResponse::error(
                'IDEMPOTENCY_IN_PROGRESS',
                __('Yêu cầu đang được xử lý, vui lòng chờ.'),
                409,
                retryable: true,
            );
        }

        $response = $next($request);
        $status = $response->getStatusCode();

        if ($status >= 500) {
            // Lỗi hệ thống: bỏ khóa để cho phép thử lại về sau.
            $record->delete();

            return $response;
        }

        $record->update([
            'response_status' => $status,
            'response_body' => $response->getContent(),
            'locked_at' => null,
        ]);

        return $response;
    }
}
