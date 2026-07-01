<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AiSuggestion extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];
}
