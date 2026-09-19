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

        // The runs either side of this one. The cached board already carries their
        // stats, so use it whenever this run is on it (the top 50); only a run
        // further down needs a query, and then there are no stats to show.
        $board = $boards->for($mapUuid)->get($categoryId, collect());
        $onBoard = $board->contains(fn ($row) => $row->UserUUID === $userUuid);

        $window = $onBoard
            ? $board->filter(fn ($row) => abs((int) $row->Rank - (int) $run->rank) <= 3)->values()
            : Cache::remember(
                "run_neighbours_{$mapUuid}_{$categoryId}_{$run->rank}",
                now()->addMinutes(2),
                fn () => DB::connection('game_mysql')
                    ->table('ranked_times as rt')
                    ->join('users as u', 'u.uuid', '=', 'rt.user_uuid')
                    ->where('rt.map_uuid', $mapUuid)
                    ->where('rt.category_id', $categoryId)
                    ->whereBetween('rt.rank', [max(1, $run->rank - 3), $run->rank + 3])
                    ->orderBy('rt.rank')
                    ->select(['rt.rank', 'rt.time', 'u.uuid as user_uuid', 'u.name as user_name'])
                    ->get()
                    // Same shape as a board row, minus the stats the timer keeps elsewhere.
                    ->map(fn ($row) => (object) [
                        'Rank' => $row->rank,
                        'time' => $row->time,
                        'UserUUID' => $row->user_uuid,
                        'UserName' => $row->user_name,
                        'Delta' => $run->best_time !== null ? (int) $row->time - (int) $run->best_time : null,
                        'sync' => null, 'strafes' => null, 'jumps' => null, 'start_speed' => null, 'overlaps' => null,
                        'nationality' => null,
                    ])
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
            'window' => $window,
            'totalRuns' => $totalRuns,
            'records' => $records,
            // Only the record holder has a recording on disk.
            'hasReplay' => (int) $run->rank === 1,
        ]);
    }
}
