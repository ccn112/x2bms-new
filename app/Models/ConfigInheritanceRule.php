<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class ConfigInheritanceRule extends Model { use BelongsToTenant; protected $guarded = []; }
