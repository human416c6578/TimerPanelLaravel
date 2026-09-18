<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = Cache::remember('admin_dashboard_stats', now()->addMinutes(2), function () {
            return [
                'players' => DB::connection('game_mysql')->table('users')->count(),
                'maps' => DB::connection('game_mysql')->table('maps')->count(),
                'categories' => DB::connection('game_mysql')->table('categories')->count(),
                'times' => DB::connection('game_mysql')->table('times')->count(),
                'ranked_times' => DB::connection('game_mysql')->table('ranked_times')->count(),
                'recent_times' => DB::connection('game_mysql')
                    ->table('times')
                    ->where('record_date', '>', DB::raw('NOW() - INTERVAL 1 DAY'))
                    ->count(),
                'active_players' => DB::connection('game_mysql')
                    ->table('times')
                    ->where('record_date', '>', DB::raw('NOW() - INTERVAL 1 DAY'))
                    ->distinct('user_uuid')
                    ->count('user_uuid'),
            ];
        });

        $latestRecords = DB::connection('game_mysql')
            ->table('times as t')
            ->join('users as u', 'u.uuid', '=', 't.user_uuid')
            ->join('maps as m', 'm.uuid', '=', 't.map_uuid')
            ->join('categories as c', 'c.id', '=', 't.category_id')
            ->leftJoin('ranked_times as rt', function ($join) {
                $join->on('rt.user_uuid', '=', 't.user_uuid')
                    ->on('rt.map_uuid', '=', 't.map_uuid')
                    ->on('rt.category_id', '=', 't.category_id');
            })
            ->select([
                't.user_uuid',
                't.map_uuid',
                't.category_id',
                't.time',
                't.record_date',
                'u.name as user_name',
                'm.name as map_name',
                'c.name as category_name',
                'rt.rank',
            ])
            ->orderByDesc('t.record_date')
            ->limit(10)
            ->get();

        return view('dashboard', compact('stats', 'latestRecords'));
    }

    public function refreshLeaderboards(): RedirectResponse
    {
        Artisan::call('leaderboard:refresh');

        Cache::flush();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Leaderboards refreshed and cache cleared.');
    }

    public function clearCaches(): RedirectResponse
    {
        Cache::flush();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Application cache cleared.');
    }
}
