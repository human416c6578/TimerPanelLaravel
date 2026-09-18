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

        return view('welcome', compact('latestTimes', 'homeStats', 'servers'));
    }
}
