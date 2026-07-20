<?php

declare(strict_types=1);

namespace App\Support\Import;

/**
 * Mô tả 1 cột import (nền dùng chung) — tương đương Filament `ImportColumn` nhưng
 * độc lập UI/package: dùng cho cả staging UI lẫn CLI, mọi tầng.
 */
final class ImportColumnSpec
{
    /**
     * @param  string  $key       Khóa trong normalized_payload (thường = tên cột DB).
     * @param  string  $label     Nhãn header kỳ vọng trong file.
     * @param  list<string>  $aliases  Các tên header thay thế (guess-match).
     * @param  bool  $required    Bắt buộc có giá trị sau normalize.
     * @param  null|callable(?string):mixed  $normalizer  Hàm chuẩn hóa (vd RowNormalizers::phone).
     * @param  list<string>  $rules  Rule validate Laravel áp cho giá trị đã normalize.
     * @param  string|null  $example  Ví dụ (để sinh file mẫu).
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly array $aliases = [],
        public readonly bool $required = false,
        public $normalizer = null,
        public readonly array $rules = [],
        public readonly ?string $example = null,
    ) {}

    /** Đọc + chuẩn hóa giá trị cột này từ 1 dòng (map header→value). */
    public function extract(array $row): mixed
    {
        $raw = RowNormalizers::value($row, $this->label, $this->aliases);

        if ($this->normalizer !== null) {
            return ($this->normalizer)($raw === null ? null : (string) $raw);
        }

        return $raw;
    }
}
