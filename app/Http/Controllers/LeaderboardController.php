<?php

namespace App\Http\Controllers;

use App\Models\PlayedTime;
use App\Models\Ranking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $topPlayedTimes = PlayedTime::select('auth_id', DB::raw('MIN(name) as name'), DB::raw('SUM(time_played) as time_played'))
            ->groupBy('auth_id')
            ->orderByDesc('time_played')
            ->limit(15)
            ->get();


        // Get top 15 ranking by score (descending)
        $topRankings = Ranking::orderByDesc('score')
            ->limit(15)
            ->with('user')
            ->get();

        return view('leaderboard.index', compact('topPlayedTimes', 'topRankings'));
    }
}
