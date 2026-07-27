<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Một bản version bất biến của trang tài liệu. Tạo bởi DocPageObserver mỗi khi
 * title/body đổi. Có thể khôi phục về trang gốc (action Khôi phục).
 */
class DocPageRevision extends Model
{
    public const UPDATED_AT = null; // chỉ có created_at

    protected $guarded = [];

    protected $casts = [
        'version' => 'integer',
        'created_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(DocPage::class, 'page_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}
