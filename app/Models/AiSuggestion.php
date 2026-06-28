<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AiSuggestion extends Model
{
    use BelongsToTenant;

    protected $guarded = [];
}
