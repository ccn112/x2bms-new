<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Addendum — Media của dự án public (ảnh/video/brochure/mặt bằng). */
class ProjectMedia extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function publicProject(): BelongsTo
    {
        return $this->belongsTo(PublicProject::class);
    }
}
