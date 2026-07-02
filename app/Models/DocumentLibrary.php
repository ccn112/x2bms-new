<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class DocumentLibrary extends Model {
    use BelongsToTenant;
    protected $guarded = [];
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function documents(): HasMany { return $this->hasMany(Document::class, 'library_id'); }
}
