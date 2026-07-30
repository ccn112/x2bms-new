<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'handover_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        // Bật: tin rao (real_estate_listings) lên feed NGAY khi tạo. Tắt (mặc
        // định): tin luôn vào hàng chờ BQL duyệt (chốt 2026-07-30).
        'listings_auto_approve' => 'boolean',
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
