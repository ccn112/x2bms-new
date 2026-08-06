<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An immutable published snapshot (pack) of a namespace+locale at a version.
 * Never edited in place — a new version supersedes; a rollback flips status to
 * 'rolled_back' so the previous published release becomes active again.
 */
class TranslationRelease extends Model
{
    protected $table = 'translation_releases';

    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function namespace(): BelongsTo
    {
        return $this->belongsTo(TranslationNamespace::class, 'namespace_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
