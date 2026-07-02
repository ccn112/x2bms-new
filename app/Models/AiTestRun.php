<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AiTestRun extends Model {
    use BelongsToTenant;
    protected $guarded = [];
    protected $casts = ['cited_sources' => 'array', 'has_citation' => 'boolean', 'score' => 'decimal:2', 'ran_at' => 'datetime'];
    public function question(): BelongsTo { return $this->belongsTo(AiTestQuestion::class, 'question_id'); }
}
