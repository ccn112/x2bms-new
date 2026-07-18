<?php

namespace App\Services\Ai;

use App\Models\AiChatSession;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * X2AI chat orchestration (ported from xweb service.ts). Split into:
 *  - preflight(): all precondition checks (rate-limit, daily cap, gating) — throws
 *    ChatException so the controller can return JSON + HTTP status BEFORE the SSE stream.
 *  - streamAnswer(): the actual token stream + best-effort persistence.
 */
class ChatService
{
    public function __construct(
        private readonly ChatStore $store,
        private readonly GuardrailResolver $guardrail,
    ) {}

    /**
     * @return array{session:AiChatSession, messages:array<int,array{role:string,content:string}>, system:string, user:?User, deviceId:?string, prompt:string}
     *
     * @throws ChatException
     */
    public function preflight(?User $user, ?string $deviceId, string $message, ?int $sessionId, ?string $surface): array
    {
        $limits = config('ai.limits');

        if (! $user && ! $deviceId) {
            throw new ChatException('device_required', 'Thiếu định danh thiết bị (X-Device-Id).', 400);
        }

        $message = trim($message);
        if ($message === '') {
            throw new ChatException('empty_message', 'Nội dung trống.', 400);
        }
        if (mb_strlen($message) > $limits['max_input_chars']) {
            throw new ChatException('input_too_long', 'Nội dung quá dài.', 413);
        }

        $actorKey = $user ? 'u:'.$user->id : 'd:'.$deviceId;

        $rlKey = 'ai-chat:'.$actorKey;
        if (RateLimiter::tooManyAttempts($rlKey, $limits['rate_per_minute'])) {
            throw new ChatException('rate_limited', 'Bạn gửi quá nhanh, thử lại sau ít giây.', 429);
        }

        $cap = $user ? $limits['auth_daily_max'] : $limits['anon_daily_max'];
        $capKey = 'ai-daily:'.now()->format('Ymd').':'.$actorKey;
        if ((int) Cache::get($capKey, 0) >= $cap) {
            throw new ChatException($user ? 'daily_limit' : 'register_for_more', 'Đã đạt giới hạn lượt hỏi hôm nay.', 429);
        }

        // Passed — consume quota + persist the user turn now (survives disconnect).
        RateLimiter::hit($rlKey, 60);
        Cache::put($capKey, (int) Cache::get($capKey, 0) + 1, now()->endOfDay());

        $session = $this->store->resolveSession($user, $deviceId, $sessionId, $surface);
        $history = $this->store->history($session, $limits['max_history_messages']);
        $this->store->appendMessage($session, 'user', $message, $user, $deviceId);

        return [
            'session' => $session,
            'messages' => array_merge($history, [['role' => 'user', 'content' => $message]]),
            'system' => $this->guardrail->systemPrompt($user, $surface),
            'user' => $user,
            'deviceId' => $deviceId,
            'prompt' => $message,
        ];
    }

    /**
     * @param  array  $ctx  result of preflight()
     * @param  callable  $onDelta  fn(string $text): void
     * @return array{session_id:int, tokens_in:int, tokens_out:int, cost:float}
     */
    public function streamAnswer(array $ctx, callable $onDelta): array
    {
        $provider = AiProviderFactory::make();

        $answer = '';
        $usage = $provider->stream($ctx['system'], $ctx['messages'], config('ai.limits.max_output_tokens'), function (string $delta) use (&$answer, $onDelta) {
            $answer .= $delta;
            $onDelta($delta);
        });

        $model = $provider->model();
        $cost = $this->estimateCost($model, $usage['tokens_in'], $usage['tokens_out']);

        try {
            $this->store->appendMessage($ctx['session'], 'assistant', $answer, $ctx['user'], $ctx['deviceId']);
            $this->store->finalize($ctx['session'], $model, $usage['tokens_in'], $usage['tokens_out'], $cost, $ctx['prompt']);
            $this->store->recordUsage($ctx['session'], $ctx['user'], $model, $usage['tokens_in'], $usage['tokens_out'], $cost, $ctx['prompt'], $answer);
        } catch (\Throwable $e) {
            report($e);
        }

        return ['session_id' => $ctx['session']->id, 'tokens_in' => $usage['tokens_in'], 'tokens_out' => $usage['tokens_out'], 'cost' => $cost];
    }

    private function estimateCost(string $model, int $tokensIn, int $tokensOut): float
    {
        $price = config("ai.prices.{$model}");
        if (! $price) {
            return 0.0;
        }

        return round(($tokensIn / 1_000_000) * $price['in'] + ($tokensOut / 1_000_000) * $price['out'], 6);
    }
}
