<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Translation namespace (e.g. x2.shared, x2.resident_app). Groups translation keys. */
class TranslationNamespace extends Model
{
    protected $table = 'translation_namespaces';

    protected $guarded = [];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function keys(): HasMany
    {
        return $this->hasMany(TranslationKey::class, 'namespace_id');
    }
}
