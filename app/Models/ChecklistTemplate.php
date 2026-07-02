<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class ChecklistTemplate extends Model {
    use BelongsToTenant, SoftDeletes;
    protected $guarded = [];
    public function items(): HasMany { return $this->hasMany(ChecklistItem::class); }
}
