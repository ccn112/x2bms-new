<?php

namespace App\Support\X2AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mode 2 backend — looks data up in X2-BMS via a configurable API.
 *
 * The real lookup API will be supplied later (config services.x2ai.data_api).
 * Until X2AI_DATA_API_URL is set, query() reports "not configured" so the
 * copilot degrades gracefully instead of erroring. Wiring the real endpoint is
 * a localized change here.
 */
class X2aiDataConnector
{
    public function configured(): bool
    {
        return ! empty(config('services.x2ai.data_api.url'));
    }

    /**
     * Execute a data lookup. $args is the model-supplied tool input
     * (e.g. ['query' => '...', 'resource' => 'residents']).
     */
    public function query(array $args): string
    {
        if (! $this->configured()) {
            return 'API tra cứu CSDL chưa được cấu hình (đặt X2AI_DATA_API_URL trong .env). '
                .'Khi có API, X2AI sẽ tra cứu dữ liệu thật ở đây.';
        }

        try {
            $cfg = config('services.x2ai.data_api');
            $response = Http::withToken($cfg['token'] ?? '')
                ->acceptJson()
                ->timeout(30)
                ->post(rtrim($cfg['url'], '/').'/lookup', [
                    'query' => $args['query'] ?? '',
                    'resource' => $args['resource'] ?? null,
                    'tenant_id' => auth()->user()?->tenant_id,
                    'project_id' => auth()->user()?->project_id,
                ]);

            if ($response->failed()) {
                return 'Tra cứu CSDL lỗi ('.$response->status().').';
            }

            // Return the raw JSON payload as text for the model to interpret.
            return $response->body();
        } catch (\Throwable $e) {
            Log::error('X2AI data lookup exception', ['message' => $e->getMessage()]);

            return 'Đã có lỗi khi tra cứu CSDL.';
        }
    }
}
