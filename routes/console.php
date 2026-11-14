<?php

use App\Domain\Payment\PaymentService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Section 4 & Section 13: Scheduled tasks
Schedule::call(function () {
    app(PaymentService::class)->expireStaleQris();
})->everyMinute()->name('qris:expire-stale');

Schedule::command('rfm:recalculate')->dailyAt('01:00')->name('rfm:nightly-recompute');
