<?php

namespace App\Http\Controllers;

use App\Models\Map;
use App\Services\MapLeaderboards;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MapController extends Controller
{
    public function index()
    {
        return view('map.list', ['total' => Map::count()]);
    }

    public function show($mapUuid)
    {
        // The leaderboard itself is the MapLeaderboard Livewire component; this
        // only guards the 404.
        return view('map.leaderboard', ['map' => Map::findOrFail($mapUuid)]);
    }

    public function deleteMapRankedTime(Request $request, string $uuid)
    {
        DB::connection('game_mysql')
            ->table('times')
            ->where('map_uuid', $uuid)
            ->where('category_id', $request->input('category_id'))
            ->where('user_uuid', $request->input('user_uuid'))
            ->delete();

        // Deleting replay if any
        if ($request->input('rank') == 1) {
            $map = Map::findOrFail($uuid);
            $categoryName = $request->input('category_name');
            $filePath = config('replays.path')."/{$map->name}/[{$categoryName}].rec";

            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        Cache::forget(MapLeaderboards::cacheKey($uuid));

        Log::info('Deleting record', [
            'map_uuid' => $uuid,
            'category_id' => $request->input('category_id'),
            'user_uuid' => $request->input('user_uuid'),
            'category_name' => $request->input('category_name'),
            'rank' => $request->input('rank'),
        ]);

        return redirect()->back()->with('status', 'Record deleted successfully!');
    }
}
