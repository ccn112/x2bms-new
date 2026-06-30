<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** WEB-UX-09-02 · Chính sách AI. */
class AiPolicy extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'config' => 'array',
    ];
}
