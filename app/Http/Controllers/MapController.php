<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Map;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MapController extends Controller
{
    public function index()
    {
        $maps = Map::orderBy('name')->get();
        return view('map.list', compact('maps'));
    }

    public function show($mapUuid)
    {
        $map = Map::findOrFail($mapUuid);

        // Try to get from cache
        $cacheKey = "map_leaderboards_{$mapUuid}";
        $leaderboards = Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($mapUuid) {
                return DB::connection("game_mysql")->select(
                    <<<SQL
                    WITH RankedRecords AS (
                        SELECT
                            RANK() OVER(PARTITION BY t.category_id ORDER BY t.`time`) AS Rank,
                            c.id AS CategoryId,
                            c.name AS CategoryName,
                            u.uuid AS UserUUID,
                            u.name AS UserName,
                            u.auth_id,
                            u.nationality,
                            t.`time`,
                            t.record_date,
                            t.start_speed
                        FROM
                            times t
                        JOIN users u ON t.user_uuid = u.uuid
                        JOIN categories c ON t.category_id = c.id
                        WHERE
                            t.map_uuid = ?
                    )
                    SELECT
                        Rank,
                        CategoryId,
                        CategoryName,
                        UserUUID,
                        UserName,
                        auth_id,
                        nationality,
                        `time`,
                        record_date,
                        start_speed
                    FROM RankedRecords
                    WHERE Rank <= 15
                    ORDER BY CategoryName ASC, Rank ASC
                    SQL
                    ,
                    [$mapUuid]
                );
            }
        );

        // Group by category ID to make display easier
        $grouped = collect($leaderboards)->groupBy("CategoryId");

        return view("map.leaderboard", [
            "map" => $map,
            "leaderboards" => $grouped,
        ]);
    }
}

?>