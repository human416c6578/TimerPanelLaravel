<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Map;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


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
            now()->addMinutes(2),
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

    public function deleteMapRankedTime(Request $request, string $uuid)
    {
        DB::connection('game_mysql')
        ->table('times')
        ->where('map_uuid', $uuid)
        ->where('category_id', $request->input('category_id'))
        ->where('user_uuid', $request->input('user_uuid'))
        ->delete();

        // Deleting replay if any
        if($request->input('rank') == 1)
        {
            $map = Map::findOrFail($uuid);
            $categoryName = $request->input('category_name');
            $filePath = "/home/csgfxeu/public_html/uploads/recording/{$map->name}/[{$categoryName}].rec";

            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $cacheKey = "map_leaderboards_{$uuid}";
        Cache::forget($cacheKey);

        Log::info('Deleting record', [
            'map_uuid'       => $uuid,
            'category_id'    => $request->input('category_id'),
            'user_uuid'      => $request->input('user_uuid'),
            'category_name'    => $request->input('category_name'),
            'rank'    => $request->input('rank'),
        ]);

        return redirect()->back()->with('status', 'Record deleted successfully!');
    }
}

?>