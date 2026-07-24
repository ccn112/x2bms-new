<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cổng thanh toán bật theo tenant + áp dụng theo dự án (vietqr|vnpay|momo).
 * Owner enable/cấu hình qua backend. `config` json chứa thông tin công khai
 * (bank account cho VietQR; tmn_code/partner_code cho VNPay/MoMo). Khoá bí mật ở ENV.
 */
class PaymentChannel extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'config' => 'array',
    ];
}
