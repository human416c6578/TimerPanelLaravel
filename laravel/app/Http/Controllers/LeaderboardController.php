<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function index(): View
    {
        // The board is the LeaderboardTable Livewire component.
        return view('leaderboard.index');
    }
}
