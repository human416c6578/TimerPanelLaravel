<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Replay storage
    |--------------------------------------------------------------------------
    |
    | Recordings are written by the game server, not by this application. The
    | panel only deletes a .rec file when the record it belongs to is deleted,
    | so it needs the directory holding them. Inside the container this is the
    | mount point of the host directory (see docker-compose.yml).
    |
    */

    'path' => env('REPLAY_STORAGE_PATH', '/home/csgfxeu/public_html/uploads/recording'),

    /*
    |--------------------------------------------------------------------------
    | Public URLs
    |--------------------------------------------------------------------------
    |
    | Where the browser downloads a recording from, and the FastDL roots the
    | replay viewer pulls map files out of.
    |
    */

    'download_url' => env('REPLAY_DOWNLOAD_URL', 'https://cs-gfx.eu/uploads/recording'),

    'fastdl' => [
        'bhop' => env('FASTDL_BHOP_URL', 'http://fastdl.cs-gfx.eu/7c1cc7a5-03d1-4475-93d5-abab1847c0f2/cstrike/'),
        'deathrun' => env('FASTDL_DEATHRUN_URL', 'http://fastdl.cs-gfx.eu/0d3d0661-5f4e-45c2-acbc-000eeeccb067/cstrike/'),
    ],

];
