<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Standard mobile API envelope (see docs/ARCHITECTURE_X2_PLATFORM_V1.md §4.4).
 *
 *   success: { data, meta: { request_id, server_time, next_cursor?, has_more? } }
 *   error:   { error: { code, message, fields?, retryable }, meta: { request_id } }
 *
 * request_id echoes the client's X-Request-Id header when present so a single call can
 * be traced across app → API → logs.
 */
final class ApiResponse
{
    public static function requestId(): string
    {
        return request()?->header('X-Request-Id') ?: (string) Str::uuid();
    }

    private static function baseMeta(array $extra = []): array
    {
        return array_merge([
            'request_id' => self::requestId(),
            'server_time' => now()->toIso8601String(),
        ], $extra);
    }

    public static function success(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => self::baseMeta($meta),
        ], $status);
    }

    /**
     * @param  array<int,mixed>  $items
     * @param  string|null  $nextCursor  opaque cursor for the next page (null = last page)
     */
    public static function paginated(array $items, ?string $nextCursor, array $meta = []): JsonResponse
    {
        return self::success($items, array_merge([
            'next_cursor' => $nextCursor,
            'has_more' => $nextCursor !== null,
        ], $meta));
    }

    public static function error(
        string $code,
        string $message,
        int $status = 400,
        ?array $fields = null,
        bool $retryable = false,
    ): JsonResponse {
        $error = [
            'code' => $code,
            'message' => $message,
            'retryable' => $retryable,
        ];
        if ($fields !== null) {
            $error['fields'] = $fields;
        }

        return response()->json([
            'error' => $error,
            'meta' => ['request_id' => self::requestId()],
        ], $status);
    }
}
