<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\GameUser;
use App\Models\Time;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class TimeController extends Controller
{
    
    public function index(Request $request)
    {
        $latestTimes = Cache::remember('latest_times_24h', now()->addMinutes(2), function () {
            return DB::connection('game_mysql')
                ->table('times as t')
                ->select(
                    't.time',
                    't.record_date',
                    't.start_speed',
                    't.user_uuid as user_uuid',
                    't.map_uuid as map_uuid',
                    't.category_id as category_id',
                    'u.name as user_name',
                    'u.auth_id',
                    'm.name as map_name',
                    'c.name as category_name',
                    'rt.rank'
                )
                ->join('users as u', 'u.uuid', '=', 't.user_uuid')
                ->join('maps as m', 'm.uuid', '=', 't.map_uuid')
                ->join('categories as c', 'c.id', '=', 't.category_id')
                ->join('ranked_times as rt', function ($join) {
                    $join->on('rt.user_uuid', '=', 't.user_uuid')
                        ->on('rt.map_uuid', '=', 't.map_uuid')
                        ->on('rt.category_id', '=', 't.category_id');
                })
                ->where('t.record_date', '>', DB::raw('NOW() - INTERVAL 1 DAY'))
                ->orderByDesc('t.record_date')
                ->limit(50)
                ->get();
        });


        return view('welcome', compact('latestTimes'));
    }
}
