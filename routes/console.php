<?php

use Illuminate\Support\Facades\Schedule;

// Sinkronisasi IOC setiap 15 menit
Schedule::command('tweetfeed:sync')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

// Backfill setiap hari pukul 02:30 UTC
Schedule::command('tweetfeed:backfill')
    ->dailyAt('02:30')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();
