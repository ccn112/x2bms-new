<?php

declare(strict_types=1);

namespace App\Support\Rules;

/**
 * Một cảnh báo rule-based: `{level, code, message, checklist[]}` (chốt BQL plan §0).
 * Render bằng x-x2.ai.suggestion-card / x-x2.status-badge.
 */
final class RiskFinding
{
    /**
     * @param  RiskLevel::*  $level
     * @param  list<string>  $checklist  Việc cần làm để gỡ cảnh báo.
     */
    public function __construct(
        public readonly string $level,
        public readonly string $code,
        public readonly string $message,
        public readonly array $checklist = [],
    ) {}

    public static function info(string $code, string $message, array $checklist = []): self
    {
        return new self(RiskLevel::INFO, $code, $message, $checklist);
    }

    public static function warning(string $code, string $message, array $checklist = []): self
    {
        return new self(RiskLevel::WARNING, $code, $message, $checklist);
    }

    public static function highRisk(string $code, string $message, array $checklist = []): self
    {
        return new self(RiskLevel::HIGH_RISK, $code, $message, $checklist);
    }

    public static function block(string $code, string $message, array $checklist = []): self
    {
        return new self(RiskLevel::POLICY_BLOCK, $code, $message, $checklist);
    }

    public function tone(): string
    {
        return RiskLevel::tone($this->level);
    }

    public function levelLabel(): string
    {
        return RiskLevel::label($this->level);
    }

    /** @return array{level:string, code:string, message:string, checklist:list<string>} */
    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'code' => $this->code,
            'message' => $this->message,
            'checklist' => $this->checklist,
        ];
    }
}
