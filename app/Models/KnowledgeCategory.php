<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** WEB-UX-09-04 · Danh mục cơ sở tri thức. */
class KnowledgeCategory extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class);
    }
}
