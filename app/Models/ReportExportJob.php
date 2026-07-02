<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ReportExportJob extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = [
        'params' => 'array',
        'completed_at' => 'datetime',
    ];
}
