<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('leaderboard:refresh', function () {
    $GameDb = DB::connection('game_mysql');

    $GameDb->statement('TRUNCATE TABLE ranked_times');

    $GameDb->statement('
          INSERT INTO ranked_times (user_uuid, map_uuid, category_id, time, rank)
        SELECT 
            t.user_uuid,
            t.map_uuid,
            t.category_id,
            t.time,
            ROW_NUMBER() OVER (PARTITION BY t.map_uuid, t.category_id ORDER BY t.time ASC)
        FROM times t
    ');
})->describe('Refresh leaderboard table');