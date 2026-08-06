<?php

declare(strict_types=1);

namespace App\Services\Localization;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Writes an admin-edited product-scope translation value (scope_type='product',
 * scope_id=''). Editing a value alone does NOT change what the resident app sees —
 * a new release must be published afterwards. Keeps the Filament Resource thin.
 */
final class TranslationValueWriter
{
    /**
     * Upsert a published product-scope value for a key+locale. Returns true when a
     * value was written (non-empty), false when skipped (blank input, nothing to do).
     */
    public function writeProductValue(int $translationKeyId, string $locale, ?string $value): bool
    {
        $value = $value === null ? '' : trim($value);

        if ($value === '') {
            return false;
        }

        DB::table('translation_values')->updateOrInsert(
            [
                'translation_key_id' => $translationKeyId,
                'locale' => $locale,
                'scope_type' => 'product',
                'scope_id' => '',
            ],
            [
                'value' => $value,
                'status' => 'published',
                'translation_method' => 'manual',
                'source_hash' => hash('sha256', $value),
                'reviewed_by' => Auth::id(),
                'published_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        AuditLog::create([
            'tenant_id' => Auth::user()?->tenant_id,
            'building_id' => Auth::user()?->building_id,
            'user_id' => Auth::id(),
            'actor_name' => Auth::user()?->name,
            'action' => 'translation.value.updated',
            'subject_type' => 'translation_key',
            'subject_id' => $translationKeyId,
            'description' => "Cập nhật bản dịch [{$locale}] cho khóa #{$translationKeyId}",
        ]);

        return true;
    }
}
