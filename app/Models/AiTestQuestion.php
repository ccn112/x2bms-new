<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class AiTestQuestion extends Model { use BelongsToTenant; protected $guarded = []; }
