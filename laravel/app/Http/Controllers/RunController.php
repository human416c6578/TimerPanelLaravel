<?php

namespace App\Http\Controllers;

use App\Models\GameUser;
use App\Models\Map;
use App\Services\CategoryRules;
use App\Services\MapLeaderboards;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RunController extends Controller
{
    /**
     * A single run: the time, everything the timer recorded about how it was
     * driven, and where it sits against the rest of the board.
     */
    public function show(string $mapUuid, int $categoryId, string $userUuid, CategoryRules $rules, MapLeaderboards $boards): View
    {
        $map = Map::findOrFail($mapUuid);
        $user = GameUser::select('uuid', 'name', 'auth_id', 'nationality')->findOrFail($userUuid);

        $run = Cache::remember(
            "run_{$mapUuid}_{$categoryId}_{$userUuid}",
            now()->addMinutes(2),
            fn () => DB::connection('game_mysql')
                ->table('times as t')
                ->join('ranked_times as rt', function ($join) {
                    $join->on('rt.user_uuid', '=', 't.user_uuid')
                        ->on('rt.map_uuid', '=', 't.map_uuid')
                        ->on('rt.category_id', '=', 't.category_id');
                })
                ->leftJoin('ranked_times as best', function ($join) {
                    $join->on('best.map_uuid', '=', 't.map_uuid')
                        ->on('best.category_id', '=', 't.category_id')
                        ->where('best.rank', '=', 1);
                })
                ->where('t.map_uuid', $mapUuid)
                ->where('t.category_id', $categoryId)
                ->where('t.user_uuid', $userUuid)
                ->select([
                    't.time',
                    't.record_date',
                    't.start_speed',
                    't.jumps',
                    't.strafes',
                    't.sync',
                    't.overlaps',
                    't.overlaps_sd',
                    'rt.rank',
                    'best.time as best_time',
                    'best.user_uuid as best_user_uuid',
                ])
                ->first()
        );

        abort_if($run === null, 404);

        // The two runs either side, so the page shows what it took to get here
        // and what is next.
        $neighbours = Cache::remember(
            "run_neighbours_{$mapUuid}_{$categoryId}_{$run->rank}",
            now()->addMinutes(2),
            fn () => DB::connection('game_mysql')
                ->table('ranked_times as rt')
                ->join('users as u', 'u.uuid', '=', 'rt.user_uuid')
                ->where('rt.map_uuid', $mapUuid)
                ->where('rt.category_id', $categoryId)
                ->whereBetween('rt.rank', [max(1, $run->rank - 2), $run->rank + 2])
                ->orderBy('rt.rank')
                ->select(['rt.rank', 'rt.time', 'u.uuid as user_uuid', 'u.name as user_name'])
                ->get()
        );

        $totalRuns = Cache::remember(
            "run_count_{$mapUuid}_{$categoryId}",
            now()->addMinutes(2),
            fn () => DB::connection('game_mysql')
                ->table('ranked_times')
                ->where('map_uuid', $mapUuid)
                ->where('category_id', $categoryId)
                ->count()
        );

        $category = $rules->find($categoryId);

        // The record on every category of this map, from the cached board.
        $records = $boards->for($mapUuid)
            ->map(fn ($runs) => $runs->firstWhere('Rank', 1))
            ->filter()
            ->values();

        return view('runs.show', [
            'map' => $map,
            'user' => $user,
            'run' => $run,
            'category' => $category,
            'categoryId' => $categoryId,
            'categoryName' => $category->name ?? 'Unknown',
            'chips' => $rules->chips($categoryId),
            'neighbours' => $neighbours,
            'totalRuns' => $totalRuns,
            'records' => $records,
            // Only the record holder has a recording on disk.
            'hasReplay' => (int) $run->rank === 1,
        ]);
    }
}
