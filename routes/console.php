<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('mov:deletar-eventos-finalizados')->dailyAt('23:55');
Schedule::command('mov:enviar-emails-aniversario')->dailyAt('08:00')->runInBackground();
