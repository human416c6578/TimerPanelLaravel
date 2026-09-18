<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Time;
use Illuminate\Support\Facades\DB;

class ReplayController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $replays = DB::connection('game_mysql')
            ->table('ranked_times as rt')
            ->join('maps as m', 'm.uuid', '=', 'rt.map_uuid')
            ->join('categories as c', 'c.id', '=', 'rt.category_id')
            ->join('users as u', 'u.uuid', '=', 'rt.user_uuid')
            ->where('rt.rank', 1)
            ->select([
                'rt.map_uuid',
                'rt.category_id',
                'rt.time',
                'm.name as map_name',
                'c.name as category_name',
                'u.uuid as user_uuid',
                'u.name as user_name',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('m.name', 'like', "%{$search}%")
                        ->orWhere('c.name', 'like', "%{$search}%")
                        ->orWhere('u.name', 'like', "%{$search}%");
                });
            })
            ->orderBy('m.name')
            ->orderBy('c.name')
            ->simplePaginate(24)
            ->withQueryString();

        if ($request->ajax()) {
            return view('replays.partials.replays-table', compact('replays'))->render();
        }

        return view('replays.list', compact('replays', 'search'));
    }

    public function show($map, $category)
    {
        $time = Time::with(['map', 'category'])
                    ->where('map_uuid', $map)
                    ->where('category_id', $category)
                    ->orderBy('time', 'asc') // best time
                    ->firstOrFail();

        $mapName = $time->map->name;
        $categoryName = $time->category->name;

        $relatedReplays = DB::connection('game_mysql')
            ->table('ranked_times as rt')
            ->join('categories as c', 'c.id', '=', 'rt.category_id')
            ->join('users as u', 'u.uuid', '=', 'rt.user_uuid')
            ->where('rt.map_uuid', $map)
            ->where('rt.rank', 1)
            ->select([
                'rt.map_uuid',
                'rt.category_id',
                'rt.time',
                'c.name as category_name',
                'u.uuid as user_uuid',
                'u.name as user_name',
            ])
            ->orderBy('c.name')
            ->get();

        return view('replays.replay', compact('time', 'mapName', 'categoryName', 'relatedReplays'));
    }

}
