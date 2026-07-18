<?php

namespace App\Services\Ai;

use App\Models\User;

/**
 * Builds the system/guardrail prompt. Slice A: config fallback + current-surface context.
 * Slice E will layer DB-driven AiGuardrailPolicy / AiPromptTemplate (per tenant) on top —
 * the schema already exists; this stays the single place that assembles the prompt.
 */
class GuardrailResolver
{
    public function systemPrompt(?User $user, ?string $surface): string
    {
        $prompt = (string) config('ai.default_system_prompt');

        if ($surface) {
            $prompt .= "\n\nNgười dùng đang ở màn: {$surface}.";
        }
        if ($user) {
            $prompt .= "\nNgười dùng đã đăng nhập (đã định danh).";
        } else {
            $prompt .= "\nNgười dùng ẩn danh — với tác vụ cần định danh, hãy mời họ đăng nhập.";
        }

        return $prompt;
    }
}
