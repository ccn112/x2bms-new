<?php

namespace App\Services\Ai;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\AiUsageLog;
use App\Models\User;

/**
 * Persistence for X2AI chat over the existing Ai* tables (the swappable "store" layer,
 * mirroring xweb store.ts). Handles both authenticated (user_id) and anonymous (device_id)
 * actors. Never throws into the stream path — persistence is best-effort.
 */
class ChatStore
{
    public function resolveSession(?User $user, ?string $deviceId, ?int $sessionId, ?string $surface): AiChatSession
    {
        if ($sessionId) {
            $session = AiChatSession::query()
                ->when($user, fn ($q) => $q->where('user_id', $user->id))
                ->when(! $user && $deviceId, fn ($q) => $q->where('device_id', $deviceId))
                ->find($sessionId);
            if ($session) {
                return $session;
            }
        }

        return AiChatSession::create([
            'tenant_id' => $user?->tenant_id,
            'user_id' => $user?->id,
            'device_id' => $user ? null : $deviceId,
            'surface' => $surface,
            'provider' => config('ai.provider'),
        ]);
    }

    /** @return array<int,array{role:string,content:string}> last N turns, oldest first */
    public function history(AiChatSession $session, int $max): array
    {
        return $session->messages()
            ->orderByDesc('id')
            ->limit($max)
            ->get(['role', 'content'])
            ->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();
    }

    public function appendMessage(AiChatSession $session, string $role, string $content, ?User $user, ?string $deviceId): void
    {
        AiChatMessage::create([
            'tenant_id' => $session->tenant_id,
            'user_id' => $user?->id,
            'device_id' => $user ? null : $deviceId,
            'ai_chat_session_id' => $session->id,
            'role' => $role,
            'content' => $content,
        ]);
    }

    public function finalize(AiChatSession $session, string $model, int $tokensIn, int $tokensOut, float $cost, string $title): void
    {
        $session->forceFill([
            'model' => $model,
            'message_count' => $session->message_count + 2,
            'tokens_in' => $session->tokens_in + $tokensIn,
            'tokens_out' => $session->tokens_out + $tokensOut,
            'est_cost' => (float) $session->est_cost + $cost,
            'last_message_at' => now(),
            'title' => $session->title ?: mb_substr($title, 0, 120),
        ])->save();
    }

    public function recordUsage(AiChatSession $session, ?User $user, string $model, int $tokensIn, int $tokensOut, float $cost, string $promptExcerpt, string $responseExcerpt): void
    {
        AiUsageLog::create([
            'tenant_id' => $session->tenant_id,
            'user_id' => $user?->id,
            'actor_name' => $user?->name ?? 'anonymous',
            'surface' => $session->surface,
            'mode' => 'context',
            'model' => $model,
            'action' => 'chat',
            'status' => 'success',
            'prompt_excerpt' => mb_substr($promptExcerpt, 0, 250),
            'response_excerpt' => mb_substr($responseExcerpt, 0, 250),
            'tokens_in' => $tokensIn,
            'tokens_out' => $tokensOut,
            'cost' => $cost,
        ]);
    }
}
