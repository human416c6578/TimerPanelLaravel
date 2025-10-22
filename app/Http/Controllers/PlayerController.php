<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\GameUser;
use App\Models\PlayedTime;
use App\Models\PlayedTimeInfo;
use App\Models\Time;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class PlayerController extends Controller
{
    
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $page = $request->input('page', 1);
        $perPage = 15;

        $cacheKey = 'players:' . md5($search . ':page:' . $page);

        $players = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($search, $perPage) {
            $query = GameUser::query()
                ->select('name', 'auth_id', 'uuid');

            if ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('auth_id', 'like', '%' . $search . '%');
            }

            return $query->orderBy('name')->paginate($perPage);
        });

        if ($request->ajax()) {
            return view('player.partials.players-table', compact('players'))->render();
        }

        return view('player.list', compact('players'));
    }

    public function profile(Request $request, $uuid)
    {
        $latestTimes = $this->getLatestTimesFromCache($uuid, $request);
        
        if ($request->ajax() || $request->get('ajax') == 1) {
            return response()->view('player.partials.latest-times', compact('latestTimes'));
        }

        $user = GameUser::select('uuid', 'name', 'auth_id')->where('uuid', $uuid)->firstOrFail();

        $authId = $user->auth_id;

        $steamId64 = $this->convertToSteamID64($authId);

        $steamAvatar = null;
        if ($steamId64) {
            $steamAvatar = Cache::remember(
                "steam_profile_{$steamId64}",
                3600,
                function () use ($steamId64) {
                    $apiKey = env("STEAM_API_KEY");
                    $response = Http::get(
                        "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/",
                        [
                            "key" => $apiKey,
                            "steamids" => $steamId64,
                        ]
                    );

                    return $response->json("response.players.0");
                }
            );
        }

        $steamData = [
            'steamid64' => $steamId64,
            'avatar' => $steamAvatar['avatarfull'] ?? null
        ];

        // Total time played (sum all time_played across servers)
        $totalTimePlayed = PlayedTime::where("auth_id", $user->auth_id)->sum(
            "time_played"
        );

        $totalTimes = Time::where("user_uuid", $uuid)->count();

        $chartData = $this->generatePlayedTimeChartData($user->auth_id);

        return view(
            "player.profile",
            compact(
                "user",
                "steamData",
                "totalTimePlayed",
                "totalTimes",
                "latestTimes",
                "chartData"
            )
        );
    }

    public function getLatestTimesFromCache(string $userUuid, Request $request)
    {
        $userTimes = Cache::remember("ranked_times_{$userUuid}", 600, function () use ($userUuid) {
            return $this->getUserRankedTimes($userUuid);
        });

        $userTimes = collect($userTimes);

        // Apply search filter if needed
        if ($request->filled("search")) {
            $search = strtolower($request->search);
            $userTimes = $userTimes->filter(
                fn($item) => str_contains(strtolower($item->MapName), $search)
            );
        }

        // Sorting - e.g. by 'record_date', 'rank', 'category_id', 'map'
        if ($request->filled("sort_by")) {
            $sortBy = $request->sort_by;
            $direction = $request->input("direction", "asc");

            $userTimes = $userTimes->sortBy(
                fn($item) => $item->{$sortBy === "map" ? "MapName" : $sortBy},
                SORT_REGULAR,
                $direction === "desc"
            );
        }

        // Paginate manually
        $page = $request->input("page", 1);
        $perPage = 14;
        $offset = ($page - 1) * $perPage;
        $paged = $userTimes->slice($offset, $perPage)->values();

        // Return as Laravel LengthAwarePaginator to keep pagination links working
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $paged,
            $userTimes->count(),
            $perPage,
            $page,
            ["path" => request()->url(), "query" => request()->query()]
        );
    }

    public function getUserRankedTimes(string $userUuid): Collection
    {
        /*
        $query = "
            WITH RankedTimes AS (
                SELECT 
                    t.user_uuid AS UserUUID,
                    m.uuid AS MapUUID,
                    m.name AS MapName,
                    c.name AS CategoryName,
                    c.id AS CategoryId,
                    t.time AS Time,
                    t.record_date AS RecordDate,
                    t.start_speed AS StartSpeed,
                    ROW_NUMBER() OVER (PARTITION BY t.map_uuid, t.category_id ORDER BY t.time ASC) AS Rank
                FROM times t
                INNER JOIN maps m ON t.map_uuid = m.uuid
                INNER JOIN categories c ON t.category_id = c.id
            )
            SELECT Rank, MapUUID, MapName, CategoryName, CategoryId, Time, RecordDate, StartSpeed
            FROM RankedTimes
            WHERE UserUUID = ?
        ";*/
        $query = "
            SELECT 
                rt.rank AS Rank,
                m.uuid        AS MapUUID,
                m.name        AS MapName,
                c.name        AS CategoryName,
                c.id          AS CategoryId,
                t.time        AS Time,
                t.record_date AS RecordDate,
                t.start_speed AS StartSpeed
            FROM ranked_times rt
            JOIN times t 
                ON t.user_uuid   = rt.user_uuid
            AND t.map_uuid    = rt.map_uuid
            AND t.category_id = rt.category_id
            JOIN maps m 
                ON m.uuid = t.map_uuid
            JOIN categories c 
                ON c.id = t.category_id
            WHERE rt.user_uuid = ?;
        ";

        return collect(DB::connection('game_mysql')->select($query, [$userUuid]));
    }

    public function deleteUserRankedTimes(string $userUuid)
{
    // $userTimes = Cache::remember("ranked_times_{$userUuid}", 600, function () use ($userUuid) {
    //     return $this->getUserRankedTimes($userUuid);
    // });

    //$userTimes = collect($userTimes);

    $userTimes = $this->getUserRankedTimes($userUuid);

    // Only rank 1 times
    $rank1Times = $userTimes->where("Rank", 1);
    $filePaths = [];

    foreach ($rank1Times as $time) {
        // Adjust path depending on how files are stored
        $filePath = "/home/csgfxeu/public_html/uploads/recording/{$time->MapName}/[{$time->CategoryName}].rec";
        
        if (file_exists($filePath)) {
            $filePaths[] = $filePath;
            unlink($filePath);
        }
    }

    // Delete from DB
    
    DB::connection('game_mysql')
        ->table('times')
        ->where('user_uuid', $userUuid)
        ->delete();
    
    // Clear cache so it doesn't serve stale data
    Cache::forget("ranked_times_{$userUuid}");

    /*
    return response()->json([
        "status" => "success",
        "message" => "User times deleted and rank1 files unlinked",
        "deleted_files" => $rank1Times->count(),
        "deleted_paths" => $filePaths
    ]);
    */

    return redirect()
    ->back()
    ->with('status', 'User times deleted and replay files unlinked.');
}

    function convertToSteamID64($authId)
    {
        // Parse the STEAM_X:Y:Z parts
        if (preg_match('/^STEAM_[0-5]:([01]):(\d+)$/', $authId, $matches)) {
            $Y = $matches[1];
            $Z = $matches[2];
            return bcadd(bcadd(bcmul($Z, "2"), $Y), "76561197960265728");
        }

        return null; // Invalid format
    }

    function generatePlayedTimeChartData(
        string $authId,
        int $daysBack = 30
    ): array {
        $startDate = Carbon::now()->subDays($daysBack);
        $days = collect(range(0, $daysBack - 1))
            ->map(
                fn($i) => Carbon::today()
                    ->subDays($i)
                    ->format("Y-m-d")
            )
            ->reverse()
            ->values()
            ->toArray();

        $sessions = PlayedTimeInfo::where("auth_id", $authId)
            ->where("date_join", ">", $startDate)
            ->orderBy("date_join")
            ->get();

        $grouped = [];

        foreach ($sessions as $session) {
            $day = Carbon::parse($session->date_join)->format("Y-m-d");
            $server = $session->server;
            $key = $server . "_" . $day;

            $time =
                strtotime($session->date_left ?? now()) -
                strtotime($session->date_join);
            if ($time < 0) {
                $time = 0;
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    "day" => $day,
                    "server" => $server,
                    "time" => 0,
                ];
            }

            $grouped[$key]["time"] += $time;
        }

        $datasets = [];
        foreach ($grouped as $entry) {
            $stack = $entry["server"];
            $day = $entry["day"];

            if (!isset($datasets[$stack])) {
                $datasets[$stack] = [
                    "label" => $stack,
                    "stack" => $stack,
                    "data" => array_fill(0, count($days), 0),
                ];
            }

            $dayIndex = array_search($day, $days);
            if ($dayIndex !== false) {
                $datasets[$stack]["data"][$dayIndex] = round(
                    $entry["time"] / 60,
                    2
                );
            }
        }

        $chartData = [
            "labels" => $days,
            "datasets" => array_values($datasets),
        ];

        return $chartData;
    }
}
