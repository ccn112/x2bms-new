<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LoginSession extends Model {
    use BelongsToTenant;
    protected $guarded = [];
    protected $casts = ['last_active_at' => 'datetime', 'is_current' => 'boolean'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
