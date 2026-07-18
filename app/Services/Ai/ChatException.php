<?php

namespace App\Services\Ai;

use RuntimeException;

/** Precondition failure (rate limit / cap / gating). Controller renders it as JSON + status. */
class ChatException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 429,
    ) {
        parent::__construct($message);
    }
}
