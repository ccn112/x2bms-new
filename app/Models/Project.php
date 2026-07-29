<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'handover_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class);
    }

    /**
     * Bản ghi tương ứng trong danh mục công khai (`public_projects`).
     *
     * Nullable và phần lớn đang null: hai bảng vốn không nối với nhau, phải
     * nối tay ở màn SuperAdmin "Nối dự án ↔ danh mục". Xem
     * `docs/COMMUNITY_DB_MAPPING.md` §4.
     */
    public function publicProject(): BelongsTo
    {
        return $this->belongsTo(PublicProject::class);
    }
}
