<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Map pictures
    |--------------------------------------------------------------------------
    |
    | URL templates to fetch a map's picture from, tried in order, with {map}
    | standing for the map's name. Comma-separated in MAP_IMAGE_SOURCES.
    |
    | There is deliberately no default: pictures are somebody's work, and which
    | source you may use is your decision. With none configured every map keeps
    | its generated cover.
    |
    | A picture is downloaded once, checked to be a real image, and stored on
    | this server; nothing is ever hot-linked.
    |
    */

    'image_sources' => array_values(array_filter(array_map('trim', explode(',', (string) env('MAP_IMAGE_SOURCES', ''))))),

    /*
    |--------------------------------------------------------------------------
    | Placeholders
    |--------------------------------------------------------------------------
    |
    | Some sources answer 200 with a "no picture" image for any name at all, even
    | a map that does not exist. Such an image would become every map's picture,
    | so downloads whose MD5 is listed here (comma-separated in
    | MAP_IMAGE_REJECT_HASHES) are treated as "not found".
    |
    | The default is banners.gametracker.rs's "NEMA SLIKE" ("no picture") image.
    |
    */

    'image_reject_hashes' => array_values(array_filter(array_map(
        fn ($hash) => strtolower(trim($hash)),
        explode(',', (string) env('MAP_IMAGE_REJECT_HASHES', 'f196e1c520b0c8d2b054f7c97bf0e80f'))
    ))),

    // How long "no picture found" is remembered before asking the sources again.
    'image_miss_hours' => (int) env('MAP_IMAGE_MISS_HOURS', 12),

    'image_max_bytes' => (int) env('MAP_IMAGE_MAX_BYTES', 2 * 1024 * 1024),

];
