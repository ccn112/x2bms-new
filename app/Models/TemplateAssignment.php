<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TemplateAssignment extends Model {
    use BelongsToTenant;
    protected $guarded = [];
    protected $casts = ['assigned_at' => 'datetime'];
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
}
