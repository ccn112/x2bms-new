<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Con trỏ phân trang thu thập dự án batdongsan theo khu vực. */
class BdsImportState extends Model
{
    protected $guarded = [];

    protected $casts = ['last_run_at' => 'datetime'];
}
