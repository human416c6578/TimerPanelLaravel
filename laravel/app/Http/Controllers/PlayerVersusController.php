<?php

namespace App\Http\Controllers;

use App\Models\GameUser;
use App\Services\PlayerProfile;
use Illuminate\View\View;

class PlayerVersusController extends Controller
{
    /**
     * Two players over every map and category they have both run. Nothing new
     * is queried: it is the two cached run lists laid side by side.
     */
    public function show(string $a, string $b, PlayerProfile $profile): View
    {
        abort_if($a === $b, 404);

        $left = GameUser::select('uuid', 'name', 'auth_id', 'nationality')->findOrFail($a);
        $right = GameUser::select('uuid', 'name', 'auth_id', 'nationality')->findOrFail($b);

        return view('players.versus', [
            'left' => $left,
            'right' => $right,
            'versus' => $profile->versus($profile->runs($a), $profile->runs($b)),
        ]);
    }
}
