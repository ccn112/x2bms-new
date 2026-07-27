<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Một hạng mục backlog thuộc một phiên bản sản phẩm.
 */
class DocVersionItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sort' => 'integer',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(DocVersion::class, 'doc_version_id');
    }

    public function refPage(): BelongsTo
    {
        return $this->belongsTo(DocPage::class, 'ref_page_id');
    }
}
