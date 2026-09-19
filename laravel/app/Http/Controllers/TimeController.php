<?php

namespace App\Http\Controllers;

use App\Services\LatestRuns;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TimeController extends Controller
{
    public function index(Request $request, LatestRuns $latestRuns)
    {
        $latestTimes = $latestRuns->window();

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

        return view('welcome', compact('latestTimes', 'homeStats', 'recordHolders', 'spotlight', 'hotMaps', 'closeCalls'));
    }
}
