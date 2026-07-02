<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class SopTemplate extends Model {
    use BelongsToTenant, SoftDeletes;
    protected $guarded = [];
    protected $casts = ['steps' => 'array'];
}
