<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class Document extends Model {
    use BelongsToTenant, SoftDeletes;
    protected $guarded = [];
    protected $casts = ['effective_from' => 'date', 'effective_to' => 'date'];
    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function library(): BelongsTo { return $this->belongsTo(DocumentLibrary::class, 'library_id'); }
}
