<?php

namespace App\Services\Ai\Providers;

/** Local echo provider — streams a canned answer, no API key/cost. Set CHAT_PROVIDER=fake. */
class FakeProvider implements LlmProvider
{
    public function stream(string $system, array $messages, int $maxTokens, callable $onDelta): array
    {
        $last = end($messages)['content'] ?? '';
        $reply = 'X2AI (chế độ thử nghiệm) đã nhận: "'.mb_substr($last, 0, 80).'". '
            .'Đây là phản hồi mẫu để kiểm thử luồng stream. Vui lòng cấu hình ANTHROPIC_API_KEY để dùng model thật.';

        foreach (preg_split('/(?<=\s)/u', $reply) as $chunk) {
            $onDelta($chunk);
        }

        return [
            'tokens_in' => (int) ceil(mb_strlen(implode(' ', array_column($messages, 'content'))) / 4),
            'tokens_out' => (int) ceil(mb_strlen($reply) / 4),
        ];
    }

    public function model(): string
    {
        return 'fake-echo';
    }
}
