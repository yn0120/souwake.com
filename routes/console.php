<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 秘密ファイル機能のデッドマンズスイッチ。「7日を1秒でも超えたら」の精度を確保するため15分間隔で実行する。
// このコマンド自体をphp artisan schedule:work（scheduler コンテナ）で常時起動しておく必要がある。
Schedule::command('secrets:enforce-retention')->everyFifteenMinutes()->withoutOverlapping();
