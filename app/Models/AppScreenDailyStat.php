<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tổng hợp nhật ký màn theo NGÀY — nguồn cho mọi biểu đồ.
 *
 * Giữ mãi (nhỏ), khác bảng thô `app_screen_events` bị dọn theo hạn lưu. Job tổng
 * hợp phải chạy TRƯỚC job dọn, nếu không mất số vĩnh viễn.
 *
 * `tenant_id`/`project_id` = **0** nghĩa là "không xác định" (thiết bị ẩn danh, chưa
 * chọn căn hộ) — dùng 0 chứ không NULL vì MySQL coi mỗi NULL là giá trị khác nhau
 * trong unique index, sẽ cho phép chèn trùng vô hạn.
 */
class AppScreenDailyStat extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            // `date:Y-m-d`, KHÔNG phải `date` trần. Cast `date` trần serialise thành
            // "2026-07-30 00:00:00" khi ghi, nên `updateOrCreate` tra bằng chuỗi
            // "2026-07-30" sẽ không khớp dòng vừa ghi → lần chạy thứ hai chèn trùng
            // và vỡ unique index. Đã gặp thật khi viết test.
            'stat_date' => 'date:Y-m-d',
        ];
    }
}
