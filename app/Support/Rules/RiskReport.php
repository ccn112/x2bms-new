<?php

declare(strict_types=1);

namespace App\Support\Rules;

/**
 * Tập hợp {@see RiskFinding} của một đối tượng (cư dân / yêu cầu duyệt…).
 * `isBlocked()` = có ít nhất 1 finding mức `policy_block` → chặn nút quyết định
 * (chỉ HQ/SuperAdmin override + ghi audit — chốt BQL plan §0).
 */
final class RiskReport
{
    /** @var list<RiskFinding> */
    private array $findings = [];

    /** @param iterable<RiskFinding> $findings */
    public function __construct(iterable $findings = [])
    {
        foreach ($findings as $finding) {
            $this->add($finding);
        }
    }

    public function add(?RiskFinding $finding): self
    {
        if ($finding !== null) {
            $this->findings[] = $finding;
        }

        return $this;
    }

    /** @return list<RiskFinding> */
    public function all(): array
    {
        return $this->findings;
    }

    public function isEmpty(): bool
    {
        return $this->findings === [];
    }

    public function isBlocked(): bool
    {
        return $this->has(RiskLevel::POLICY_BLOCK);
    }

    public function has(string $level): bool
    {
        foreach ($this->findings as $f) {
            if ($f->level === $level) {
                return true;
            }
        }

        return false;
    }

    /** Mức cao nhất trong report (null nếu rỗng). @return RiskLevel::*|null */
    public function highestLevel(): ?string
    {
        $highest = null;
        $highestSeverity = -1;
        foreach ($this->findings as $f) {
            $s = RiskLevel::severity($f->level);
            if ($s > $highestSeverity) {
                $highestSeverity = $s;
                $highest = $f->level;
            }
        }

        return $highest;
    }

    /** Tone badge tổng hợp cho card AI (theo mức cao nhất). */
    public function tone(): string
    {
        $level = $this->highestLevel();

        return $level === null ? 'green' : RiskLevel::tone($level);
    }

    /** Số finding từ mức trở lên (để hiện KPI/chip). */
    public function countFrom(string $level): int
    {
        $min = RiskLevel::severity($level);

        return count(array_filter(
            $this->findings,
            static fn (RiskFinding $f): bool => RiskLevel::severity($f->level) >= $min,
        ));
    }

    /** @return list<array{level:string, code:string, message:string, checklist:list<string>}> */
    public function toArray(): array
    {
        return array_map(static fn (RiskFinding $f): array => $f->toArray(), $this->findings);
    }

    /**
     * Đổ vào FAB AI context (ProvidesAiContext::shareAiContext) —
     * lines = message, suggestions = checklist gộp.
     *
     * @return array{lines:list<string>, suggestions:list<array{title:string, sub:string}>}
     */
    public function toAiContext(): array
    {
        $lines = [];
        $suggestions = [];
        foreach ($this->findings as $f) {
            $lines[] = '['.$f->levelLabel().'] '.$f->message;
            foreach ($f->checklist as $item) {
                $suggestions[] = ['title' => $item, 'sub' => $f->levelLabel()];
            }
        }

        return ['lines' => $lines, 'suggestions' => $suggestions];
    }
}
