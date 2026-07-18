<?php

namespace App\Services\Ai;

use App\Services\Ai\Providers\AnthropicProvider;
use App\Services\Ai\Providers\FakeProvider;
use App\Services\Ai\Providers\LlmProvider;
use InvalidArgumentException;

class AiProviderFactory
{
    public static function make(?string $provider = null): LlmProvider
    {
        $provider ??= config('ai.provider');

        return match ($provider) {
            'anthropic' => new AnthropicProvider(config('ai.providers.anthropic')),
            'fake' => new FakeProvider,
            default => throw new InvalidArgumentException("Unknown AI provider: {$provider}"),
        };
    }
}
