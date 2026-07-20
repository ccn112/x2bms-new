<?php

declare(strict_types=1);

namespace App\Support\Import;

/**
 * Một vấn đề gặp phải ở 1 dòng khi import (cảnh báo hoặc lỗi) — nền dùng chung.
 * Khi lưu vào staging (`import_batch_rows.validation_errors`) thì dùng `toArray()`.
 */
final class RowIssue
{
    public const LEVEL_WARNING = 'warning';

    public const LEVEL_ERROR = 'error';

    /**
     * @param  int  $rowNumber  Số dòng trong file (1-based; 0 = lỗi cấp file, không theo dòng).
     * @param  self::LEVEL_*  $level
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly int $rowNumber,
        public readonly string $level,
        public readonly string $message,
        public readonly array $context = [],
    ) {}

    public static function warning(int $rowNumber, string $message, array $context = []): self
    {
        return new self($rowNumber, self::LEVEL_WARNING, $message, $context);
    }

    public static function error(int $rowNumber, string $message, array $context = []): self
    {
        return new self($rowNumber, self::LEVEL_ERROR, $message, $context);
    }

    public function isError(): bool
    {
        return $this->level === self::LEVEL_ERROR;
    }

    /** @return array{row:int, level:string, message:string, context:array<string,mixed>} */
    public function toArray(): array
    {
        return [
            'row' => $this->rowNumber,
            'level' => $this->level,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
