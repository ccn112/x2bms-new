<?php

declare(strict_types=1);

namespace App\Support\Import;

use App\Support\Import\Profiles\ResidentImportProfile;
use InvalidArgumentException;

/**
 * Ánh xạ `import_batches.import_type` → ImportProfile — để Job nền dựng lại profile
 * đúng loại khi commit bất đồng bộ. Thêm loại mới (căn hộ, xe…) khai báo tại đây.
 */
final class ImportProfileRegistry
{
    public static function for(string $importType): ImportProfile
    {
        return match ($importType) {
            'residents' => new ResidentImportProfile,
            default => throw new InvalidArgumentException("Không hỗ trợ import_type: {$importType}"),
        };
    }
}
