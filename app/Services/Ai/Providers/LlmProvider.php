<?php

namespace App\Services\Ai\Providers;

/**
 * Streaming LLM abstraction (ported design from xweb providers.ts). Adding a provider =
 * implement this interface + register in AiProviderFactory + add prices to config/ai.php.
 * Nothing above this layer knows which vendor is in use.
 */
interface LlmProvider
{
    /**
     * Stream a completion. Emits each text delta via $onDelta(string $text).
     *
     * @param  array<int,array{role:string,content:string}>  $messages
     * @return array{tokens_in:int, tokens_out:int}  token usage, resolved after the stream ends
     */
    public function stream(string $system, array $messages, int $maxTokens, callable $onDelta): array;

    public function model(): string;
}
