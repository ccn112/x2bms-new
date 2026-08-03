<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Kỳ chịu trách nhiệm tài chính của một căn hộ (canonical, P1b/D11-D12).
 *
 * `scope` = NULL hoặc `['all']` → chịu MỌI family. Mảng family code (`['electricity','water']`)
 * → chỉ chịu các family đó (owner/tenant split). `liable_to` NULL = còn hiệu lực.
 */
class LiabilityPeriod extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'liable_from' => 'date',
        'liable_to' => 'date',
        'scope' => 'array',
    ];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    /** Liability còn hiệu lực (chưa đóng). */
    public function isOpen(): bool
    {
        return $this->liable_to === null;
    }

    /** Có chịu family này không (NULL/['all'] = mọi family). */
    public function coversFamily(string $familyCode): bool
    {
        $scope = $this->scope;

        return $scope === null || in_array('all', $scope, true) || in_array($familyCode, $scope, true);
    }
}
