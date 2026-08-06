<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A single translatable key inside a namespace (product/source of truth). */
class TranslationKey extends Model
{
    protected $table = 'translation_keys';

    protected $guarded = [];

    protected $casts = [
        'placeholders' => 'array',
        'allow_tenant_override' => 'boolean',
        'is_critical' => 'boolean',
    ];

    public function namespace(): BelongsTo
    {
        return $this->belongsTo(TranslationNamespace::class, 'namespace_id');
    }
}
