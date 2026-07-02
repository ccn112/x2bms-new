<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class AiKnowledgeSource extends Model {
    use BelongsToTenant;
    protected $guarded = [];
    protected $casts = ['size_gb' => 'decimal:2', 'auto_sync' => 'boolean', 'last_synced_at' => 'datetime'];
}
