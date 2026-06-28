<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class IocAlert extends Model
{
    use BelongsToTenant;

    protected $guarded = [];
}
