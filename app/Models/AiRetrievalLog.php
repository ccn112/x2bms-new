<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Addendum — Log retrieval RAG (tài liệu lấy/ chặn + snapshot quyền). */
class AiRetrievalLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'retrieved_document_ids_json' => 'array',
        'blocked_document_ids_json' => 'array',
        'permission_snapshot_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
