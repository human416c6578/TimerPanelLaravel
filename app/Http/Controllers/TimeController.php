<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\GameUser;
use App\Models\Time;
use App\Services\GameServerQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class TimeController extends Controller
{
    public function index(Request $request, GameServerQuery $serverQuery)
    {
        $latestTimes = Cache::remember(
            "latest_times_24h",
            now()->addMinutes(2),
            function () {
                $subQuery = DB::connection("game_mysql")
                    ->table("times")
                    ->select(
                        "time",
                        "record_date",
                        "start_speed",
                        "user_uuid",
                        "map_uuid",
                        "category_id",
                    )
                    ->where(
                        "record_date",
                        ">",
                        DB::raw("NOW() - INTERVAL 1 DAY"),
                    )
                    ->orderByDesc("record_date")
                    ->limit(50);

                return DB::connection("game_mysql")
                    ->query()
                    ->fromSub($subQuery, "t")
                    ->select(
                        "t.time",
                        "t.record_date",
                        "t.start_speed",
                        "t.user_uuid",
                        "t.map_uuid",
                        "t.category_id",
                        "u.name as user_name",
                        "u.auth_id",
                        "m.name as map_name",
                        "c.name as category_name",
                        "rt.rank",
                    )
                    ->join("users as u", "u.uuid", "=", "t.user_uuid")
                    ->join("maps as m", "m.uuid", "=", "t.map_uuid")
                    ->join("categories as c", "c.id", "=", "t.category_id")
                    ->join("ranked_times as rt", function ($join) {
                        $join
                            ->on("rt.user_uuid", "=", "t.user_uuid")
                            ->on("rt.map_uuid", "=", "t.map_uuid")
                            ->on("rt.category_id", "=", "t.category_id");
                    })
                    ->orderByDesc("t.record_date")
                    ->get();
            },
        );

        $homeStats = Cache::remember(
            "home_stats",
            now()->addMinutes(2),
            function () {
                return [
                    "players" => DB::connection("game_mysql")
                        ->table("users")
                        ->count(),
                    "maps" => DB::connection("game_mysql")
                        ->table("maps")
                        ->count(),
                    "categories" => DB::connection("game_mysql")
                        ->table("categories")
                        ->count(),
                    "records" => DB::connection("game_mysql")
                        ->table("times")
                        ->count(),
                    "recent_records" => DB::connection("game_mysql")
                        ->table("times")
                        ->where(
                            "record_date",
                            ">",
                            DB::raw("NOW() - INTERVAL 1 DAY"),
                        )
                        ->count(),
                    "active_players" => DB::connection("game_mysql")
                        ->table("times")
                        ->where(
                            "record_date",
                            ">",
                            DB::raw("NOW() - INTERVAL 1 DAY"),
                        )
                        ->distinct("user_uuid")
                        ->count("user_uuid"),
                ];
            },
        );

        $servers = Cache::remember(
            "home_live_servers",
            now()->addSeconds(45),
            function () use ($serverQuery) {
                return collect([
                    [
                        "name" => "Bhop",
                        "host" => "bhop.laleagane.ro",
                        "port" => 27015,
                        "aliases" => [],
                    ],
                    [
                        "name" => "Deathrun",
                        "host" => "dr.laleagane.ro",
                        "port" => 27015,
                        "aliases" => ["dr.llg.ro"],
                    ],
                    [
                        "name" => "Speedrun",
                        "host" => "bhop.laleagane.ro",
                        "port" => 27016,
                        "aliases" => [],
                    ],
                    [
                        "name" => "Bhop Brazil",
                        "host" => "190.115.197.245",
                        "port" => 27015,
                        "aliases" => [],
                    ],
                ])->map(function ($server) use ($serverQuery) {
                    $queryHosts = array_merge(
                        [$server["host"]],
                        $server["aliases"],
                    );
                    $info = null;
                    $resolvedHost = $server["host"];

                    foreach ($queryHosts as $host) {
                        $info = $serverQuery->info($host, $server["port"]);

                        if ($info !== null) {
                            $resolvedHost = $host;
                            break;
                        }
                    }

                    return array_merge($server, [
                        "query_host" => $resolvedHost,
                        "online" => $info !== null,
                        "map" => $info["map"] ?? null,
                        "players" => $info["players"] ?? null,
                        "max_players" => $info["max_players"] ?? null,
                        "server_name" => $info["name"] ?? null,
                    ]);
                });
            },
        );

        return view("welcome", compact("latestTimes", "homeStats", "servers"));
    }
}
