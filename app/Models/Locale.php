<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Locale catalog (I18N). PK is the BCP-47 code (e.g. vi-VN). Read-mostly master data
 * managed from the SuperAdmin Translation Center.
 */
class Locale extends Model
{
    protected $table = 'locales';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];
}
