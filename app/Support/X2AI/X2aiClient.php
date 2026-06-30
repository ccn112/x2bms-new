<?php

namespace App\Support\X2AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client over the Anthropic Messages API for the X2AI Copilot (WEB-UX-09).
 *
 * Uses Laravel's HTTP client (Guzzle) rather than the official SDK to avoid an
 * extra Composer dependency; the request/response shape matches the Messages API
 * exactly, so swapping to anthropic-ai/sdk later is a localized change.
 *
 * Supports tool use (Mode 2 — database lookup): when $tools is passed and the
 * model emits a tool_use, the loop executes it via X2aiDataConnector and feeds
 * the result back until the model produces a final text answer.
 *
 * Config in config/services.php → 'x2ai'.
 */
class X2aiClient
{
    /**
     * @param  array<int, mixed>  $messages  Conversation history; each item's `content`
     *                                        may be a string or an array of content blocks.
     * @param  array<int, array<string, mixed>>  $tools  Tool definitions (empty = plain chat).
     */
    public function ask(array $messages, ?string $system = null, array $tools = []): string
    {
        $cfg = config('services.x2ai');

        if (empty($cfg['key'])) {
            return 'X2AI chưa được cấu hình API key. Vui lòng đặt X2AI_API_KEY trong .env.';
        }

        try {
            for ($iteration = 0; $iteration < 4; $iteration++) {
                $response = Http::withHeaders([
                    'x-api-key' => $cfg['key'],
                    'anthropic-version' => $cfg['version'],
                    'content-type' => 'application/json',
                ])
                    ->timeout(60)
                    ->post($cfg['base_url'], array_filter([
                        'model' => $cfg['model'],
                        'max_tokens' => $cfg['max_tokens'],
                        'system' => $system,
                        'messages' => $messages,
                        'tools' => $tools ?: null,
                    ]));

                if ($response->failed()) {
                    Log::warning('X2AI request failed', ['status' => $response->status(), 'body' => $response->json()]);

                    return 'Xin lỗi, X2AI tạm thời không phản hồi được ('.$response->status().'). Vui lòng thử lại.';
                }

                $content = $response->json('content', []);
                $stopReason = $response->json('stop_reason');

                // Tool-use loop (Mode 2): execute requested tools, feed results back.
                if ($tools && $stopReason === 'tool_use') {
                    $messages[] = ['role' => 'assistant', 'content' => $content];

                    $toolResults = [];
                    foreach ($content as $block) {
                        if (($block['type'] ?? null) === 'tool_use') {
                            $toolResults[] = [
                                'type' => 'tool_result',
                                'tool_use_id' => $block['id'],
                                'content' => $this->runTool($block['name'] ?? '', $block['input'] ?? []),
                            ];
                        }
                    }
                    $messages[] = ['role' => 'user', 'content' => $toolResults];

                    continue; // ask again with the tool results
                }

                $text = collect($content)->where('type', 'text')->pluck('text')->implode("\n");

                return $text !== '' ? $text : 'X2AI không trả về nội dung.';
            }

            return 'X2AI cần quá nhiều bước tra cứu — vui lòng hỏi cụ thể hơn.';
        } catch (\Throwable $e) {
            Log::error('X2AI exception', ['message' => $e->getMessage()]);

            return 'Xin lỗi, đã có lỗi khi gọi X2AI. Vui lòng thử lại sau.';
        }
    }

    /** The single Mode-2 tool definition (database lookup). */
    public static function dataLookupTool(): array
    {
        return [
            'name' => 'lookup_data',
            'description' => 'Tra cứu dữ liệu thật trong hệ thống X2-BMS (cư dân, căn hộ, phí, công nợ, '
                .'phản ánh, công việc...). Dùng khi câu hỏi cần số liệu/bản ghi cụ thể.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Nội dung cần tra cứu, mô tả rõ.'],
                    'resource' => ['type' => 'string', 'description' => 'Nhóm dữ liệu, vd: residents, apartments, statements, debts, feedback, work_orders.'],
                ],
                'required' => ['query'],
            ],
        ];
    }

    private function runTool(string $name, array $input): string
    {
        return match ($name) {
            'lookup_data' => app(X2aiDataConnector::class)->query($input),
            default => 'Công cụ không hỗ trợ: '.$name,
        };
    }
}
