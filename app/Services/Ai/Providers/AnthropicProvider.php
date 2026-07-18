<?php

namespace App\Services\Ai\Providers;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Anthropic Messages API streaming. Emits content_block_delta text; reads token usage
 * from message_start (input) + message_delta (output). Key stays server-side only.
 */
class AnthropicProvider implements LlmProvider
{
    /** @param array{api_key:?string, model:string, base_url:string, version:string} $cfg */
    public function __construct(private readonly array $cfg) {}

    public function model(): string
    {
        return $this->cfg['model'];
    }

    public function stream(string $system, array $messages, int $maxTokens, callable $onDelta): array
    {
        if (empty($this->cfg['api_key'])) {
            throw new RuntimeException('ANTHROPIC_API_KEY chưa cấu hình.');
        }

        $response = Http::withOptions(['stream' => true])
            ->withHeaders([
                'x-api-key' => $this->cfg['api_key'],
                'anthropic-version' => $this->cfg['version'],
                'content-type' => 'application/json',
            ])
            ->post(rtrim($this->cfg['base_url'], '/').'/v1/messages', [
                'model' => $this->cfg['model'],
                'max_tokens' => $maxTokens,
                'system' => $system,
                'messages' => array_map(fn ($m) => [
                    'role' => $m['role'],
                    'content' => $m['content'],
                ], $messages),
                'stream' => true,
            ]);

        $body = $response->toPsrResponse()->getBody();
        $tokensIn = 0;
        $tokensOut = 0;
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(1024);

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if (! str_starts_with($line, 'data:')) {
                    continue;
                }
                $json = json_decode(trim(substr($line, 5)), true);
                if (! is_array($json)) {
                    continue;
                }

                $type = $json['type'] ?? '';
                if ($type === 'content_block_delta' && isset($json['delta']['text'])) {
                    $onDelta($json['delta']['text']);
                } elseif ($type === 'message_start') {
                    $tokensIn = (int) ($json['message']['usage']['input_tokens'] ?? 0);
                } elseif ($type === 'message_delta') {
                    $tokensOut = (int) ($json['usage']['output_tokens'] ?? $tokensOut);
                }
            }
        }

        return ['tokens_in' => $tokensIn, 'tokens_out' => $tokensOut];
    }
}
