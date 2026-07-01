<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** WEB-UX-09-02 · Prompt & phân loại + 09-01 Gợi ý nhanh. Mở rộng theo addendum (use_case/system_prompt). */
class AiPromptTemplate extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected $casts = ['variables_json' => 'array'];
}
