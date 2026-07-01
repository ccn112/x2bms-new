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
