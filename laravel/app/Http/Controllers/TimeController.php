<?php

namespace App\Http\Controllers;

use App\Services\ServerStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TimeController extends Controller
{
    public function index(Request $request, ServerStatus $serverStatus)
    {
        $latestTimes = Cache::remember(
            'latest_times_24h',
            now()->addMinutes(2),
            function () {
                $subQuery = DB::connection('game_mysql')
                    ->table('times')
                    ->select(
                        'time',
                        'record_date',
                        'start_speed',
                        'user_uuid',
                        'map_uuid',
                        'category_id',
                    )
                    ->where(
                        'record_date',
                        '>',
                        DB::raw('NOW() - INTERVAL 1 DAY'),
                    )
                    ->orderByDesc('record_date')
                    ->limit(50);

                return DB::connection('game_mysql')
                    ->query()
                    ->fromSub($subQuery, 't')
                    ->select(
                        't.time',
                        't.record_date',
                        't.start_speed',
                        't.user_uuid',
                        't.map_uuid',
                        't.category_id',
                        'u.name as user_name',
                        'u.auth_id',
                        'm.name as map_name',
                        'c.name as category_name',
                        'rt.rank',
                        // The record on that map/category, so the feed can show
                        // how far off the pace each run was.
                        'best.time as best_time',
                    )
                    ->join('users as u', 'u.uuid', '=', 't.user_uuid')
                    ->join('maps as m', 'm.uuid', '=', 't.map_uuid')
                    ->join('categories as c', 'c.id', '=', 't.category_id')
                    ->join('ranked_times as rt', function ($join) {
                        $join
                            ->on('rt.user_uuid', '=', 't.user_uuid')
                            ->on('rt.map_uuid', '=', 't.map_uuid')
                            ->on('rt.category_id', '=', 't.category_id');
                    })
                    ->leftJoin('ranked_times as best', function ($join) {
                        $join
                            ->on('best.map_uuid', '=', 't.map_uuid')
                            ->on('best.category_id', '=', 't.category_id')
                            ->where('best.rank', '=', 1);
                    })
                    ->orderByDesc('t.record_date')
                    ->get();
            },
        );

        $homeStats = Cache::remember(
            'home_stats',
            now()->addMinutes(2),
            function () {
                return [
                    'players' => DB::connection('game_mysql')
                        ->table('users')
                        ->count(),
                    'maps' => DB::connection('game_mysql')
                        ->table('maps')
                        ->count(),
                    'categories' => DB::connection('game_mysql')
                        ->table('categories')
                        ->count(),
                    'records' => DB::connection('game_mysql')
                        ->table('times')
                        ->count(),
                    'recent_records' => DB::connection('game_mysql')
                        ->table('times')
                        ->where(
                            'record_date',
                            '>',
                            DB::raw('NOW() - INTERVAL 1 DAY'),
                        )
                        ->count(),
                    'active_players' => DB::connection('game_mysql')
                        ->table('times')
                        ->where(
                            'record_date',
                            '>',
                            DB::raw('NOW() - INTERVAL 1 DAY'),
                        )
                        ->distinct('user_uuid')
                        ->count('user_uuid'),
                ];
            },
        );

        $servers = $serverStatus->all();

        // Who took the most records in the last 24 hours, from the feed we
        // already have — no further query.
        $recordHolders = collect($latestTimes)
            ->where('rank', 1)
            ->groupBy('user_uuid')
            ->map(fn ($runs) => (object) ['name' => $runs->first()->user_name, 'uuid' => $runs->first()->user_uuid, 'records' => $runs->count()])
            ->sortByDesc('records')
            ->take(3)
            ->values();

        $feed = collect($latestTimes);

        // The headline of the day: the newest record, or failing that the newest run.
        $spotlight = $feed->firstWhere('rank', 1) ?? $feed->first();

        // Maps with the most runs in the window.
        $hotMaps = $feed
            ->groupBy('map_uuid')
            ->map(fn ($runs) => (object) ['uuid' => $runs->first()->map_uuid, 'name' => $runs->first()->map_name, 'runs' => $runs->count()])
            ->sortByDesc('runs')
            ->take(5)
            ->values();

        // Runs within 2% of the record without taking it: the ones worth watching.
        $closeCalls = $feed
            ->filter(fn ($run) => (int) $run->rank > 1
                && $run->best_time !== null
                && ((int) $run->time - (int) $run->best_time) / max(1, (int) $run->best_time) <= 0.02)
            ->sortBy(fn ($run) => (int) $run->time - (int) $run->best_time)
            ->take(5)
            ->values();

        return view('welcome', compact('latestTimes', 'homeStats', 'servers', 'recordHolders', 'spotlight', 'hotMaps', 'closeCalls'));
    }
}
