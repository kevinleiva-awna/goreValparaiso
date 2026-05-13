<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Respaldo de observaciones cada 48h durante consultas activas (exigencia
// del brief). Ejecuta a las 02:00 (CLT). En produccion AWS, el queue worker
// y el scheduler corren via supervisord + cron de EventBridge.
Schedule::command('gore:backup-observations')
    ->cron('0 2 */2 * *')
    ->onOneServer()
    ->name('gore-backup-observations')
    ->withoutOverlapping();
