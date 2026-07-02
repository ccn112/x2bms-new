<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class AiKnowledgeSyncLog extends Model { use BelongsToTenant; protected $guarded = []; protected $casts = ['ran_at' => 'datetime']; }
