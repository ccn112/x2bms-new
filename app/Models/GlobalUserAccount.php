<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Addendum — Tài khoản gốc toàn hệ thống (public → verified → resident/employee...). */
class GlobalUserAccount extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata_json' => 'array',
        'first_registered_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function bindingRequests(): HasMany
    {
        return $this->hasMany(ResidentBindingRequest::class, 'user_account_id');
    }

    public function unitBindings(): HasMany
    {
        return $this->hasMany(ResidentUnitBinding::class, 'user_account_id');
    }
}
