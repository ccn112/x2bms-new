<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Addendum — Liên kết dự án của tenant với dự án public (kế thừa nội dung). */
class TenantProjectLink extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['override_content_json' => 'array', 'linked_at' => 'datetime'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function publicProject(): BelongsTo
    {
        return $this->belongsTo(PublicProject::class);
    }
}
