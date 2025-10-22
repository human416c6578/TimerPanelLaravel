<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Time;
use App\Models\Map;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ReplayController extends Controller
{
    
    public function show($map, $category)
    {
        $time = Time::with(['map', 'category'])
                    ->where('map_uuid', $map)
                    ->where('category_id', $category)
                    ->orderBy('time', 'asc') // best time
                    ->firstOrFail();

        $mapName = $time->map->name;
        $categoryName = $time->category->name;

        return view('replays.replay', compact('time', 'mapName', 'categoryName'));
    }

}
