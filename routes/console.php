<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly: move stale log/audit rows into their *_archive clones (config/archive.php).
Schedule::command('logs:archive')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->onOneServer();

// Số lượt cài app từ Google Play + App Store. Chạy 03:15 chứ không sớm hơn: cả hai
// store chốt số liệu theo giờ Mỹ nên số của "hôm qua" chỉ có vào rạng sáng giờ VN.
// Lệnh này KHÔNG fail khi chưa cấu hình credential (in `not_configured`), nên bật
// sẵn trong lúc chờ cấp key mà cron không kêu mỗi ngày.
Schedule::command('x2:sync-store-installs')
    ->dailyAt('03:15')
    ->withoutOverlapping()
    ->onOneServer();
