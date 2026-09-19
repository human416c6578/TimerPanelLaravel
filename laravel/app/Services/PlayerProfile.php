<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Everything the profile page says about a player beyond the raw list of times:
 * how their ranks are distributed, which records are within reach, and the
 * medals the timer has already awarded them.
 */
class PlayerProfile
{
    /**
     * Every ranked run a player has, with the stats the timer recorded and the
     * gap to the record on that map/category. Cached, and shared by the profile
     * summary and the records table.
     *
     * @return Collection<int, object>
     */
    public function runs(string $userUuid): Collection
    {
        return Cache::remember("ranked_times_{$userUuid}", 600, fn () => $this->fetchRuns($userUuid));
    }

    /**
     * Uncached. `Rank` and `sync` are reserved words, hence the backticks. The
     * join on the rank-1 row of each map/category gives the record to compare
     * against without a second round trip.
     *
     * @return Collection<int, object>
     */
    public function fetchRuns(string $userUuid): Collection
    {
        $query = '
            SELECT
                rt.`rank`     AS `Rank`,
                m.uuid        AS MapUUID,
                m.name        AS MapName,
                c.name        AS CategoryName,
                c.id          AS CategoryId,
                t.`time`      AS `Time`,
                t.record_date AS RecordDate,
                t.start_speed AS StartSpeed,
                t.jumps       AS Jumps,
                t.strafes     AS Strafes,
                t.`sync`      AS `Sync`,
                t.overlaps    AS Overlaps,
                t.overlaps_sd AS OverlapsSd,
                best.`time`   AS BestTime,
                t.`time` - best.`time` AS `Delta`,
                best.user_uuid AS BestUserUUID,
                total.runs    AS Runs
            FROM ranked_times rt
            JOIN times t
                ON t.user_uuid   = rt.user_uuid
            AND t.map_uuid    = rt.map_uuid
            AND t.category_id = rt.category_id
            JOIN maps m
                ON m.uuid = t.map_uuid
            JOIN categories c
                ON c.id = t.category_id
            LEFT JOIN ranked_times best
                ON best.map_uuid    = rt.map_uuid
            AND best.category_id = rt.category_id
            AND best.`rank`      = 1
            LEFT JOIN (
                SELECT everyone.map_uuid, everyone.category_id, COUNT(*) AS runs
                FROM ranked_times everyone
                JOIN ranked_times mine
                    ON mine.map_uuid    = everyone.map_uuid
                AND mine.category_id = everyone.category_id
                AND mine.user_uuid   = ?
                GROUP BY everyone.map_uuid, everyone.category_id
            ) total
                ON total.map_uuid    = rt.map_uuid
            AND total.category_id = rt.category_id
            WHERE rt.user_uuid = ?
        ';

        // Counting only the maps this player has run keeps the subquery from
        // grouping the whole ranked_times table on every profile view.
        return collect(DB::connection('game_mysql')->select($query, [$userUuid, $userUuid]));
    }

    /**
     * Rank distribution, computed from the times the profile already loaded —
     * no extra query.
     *
     * @param  Collection<int, object>  $runs
     * @return array{first: int, second: int, third: int, top10: int, total: int, best: int|null}
     */
    public function rankSummary(Collection $runs): array
    {
        $ranks = $runs->pluck('Rank')->filter(fn ($rank) => $rank !== null)->map(fn ($rank) => (int) $rank);

        return [
            'first' => $ranks->filter(fn ($rank) => $rank === 1)->count(),
            'second' => $ranks->filter(fn ($rank) => $rank === 2)->count(),
            'third' => $ranks->filter(fn ($rank) => $rank === 3)->count(),
            'top10' => $ranks->filter(fn ($rank) => $rank <= 10)->count(),
            'total' => $runs->count(),
            'best' => $ranks->min(),
        ];
    }

    /**
     * Records the player is closest to taking: not theirs yet, smallest gap
     * first. Also computed from the loaded runs.
     *
     * @param  Collection<int, object>  $runs
     * @return Collection<int, object>
     */
    public function withinReach(Collection $runs, int $limit = 5): Collection
    {
        return $runs
            ->filter(fn ($run) => (int) ($run->Rank ?? 0) > 1 && ($run->Delta ?? null) !== null)
            ->sortBy(fn ($run) => (int) $run->Delta)
            ->take($limit)
            ->values();
    }

    /**
     * The headline numbers of a profile, all from the loaded runs.
     *
     * "Typical position" is the average of rank/runs — 8% means a player usually
     * finishes in the top 8% of whoever ran that map. It compares across maps
     * of very different sizes, which a raw rank does not. It needs fields of at
     * least five runners to mean anything, so smaller ones are left out.
     *
     * @param  Collection<int, object>  $runs
     * @return array{
     *     total: int, records: int, podiums: int, top10: int,
     *     recordShare: float, top10Share: float,
     *     avgSync: float|null, typicalPosition: float|null,
     *     categories: Collection<int, object>, form: Collection<int, object>
     * }
     */
    public function insights(Collection $runs): array
    {
        $total = $runs->count();
        $ranked = $runs->filter(fn ($run) => $run->Rank !== null);

        $records = $ranked->filter(fn ($run) => (int) $run->Rank === 1)->count();
        $podiums = $ranked->filter(fn ($run) => (int) $run->Rank <= 3)->count();
        $top10 = $ranked->filter(fn ($run) => (int) $run->Rank <= 10)->count();

        $synced = $runs->filter(fn ($run) => ($run->Sync ?? null) !== null);

        // A field of two makes every finish "top 50%" or "top 100%", which says
        // nothing about the player. Only fields of five or more count.
        $positions = $ranked
            ->filter(fn ($run) => (int) ($run->Runs ?? 0) >= 5)
            ->map(fn ($run) => (int) $run->Rank / (int) $run->Runs);

        return [
            'total' => $total,
            'records' => $records,
            'podiums' => $podiums,
            'top10' => $top10,
            'recordShare' => $total > 0 ? $records / $total : 0.0,
            'top10Share' => $total > 0 ? $top10 / $total : 0.0,
            'avgSync' => $synced->isEmpty() ? null : (float) $synced->avg(fn ($run) => (float) $run->Sync),
            'typicalPosition' => $positions->isEmpty() ? null : (float) $positions->avg(),

            // Where they actually run: categories by volume, with how many are records.
            'categories' => $runs
                ->groupBy('CategoryName')
                ->map(fn ($group, $name) => (object) [
                    'name' => $name,
                    'runs' => $group->count(),
                    'records' => $group->filter(fn ($run) => (int) $run->Rank === 1)->count(),
                ])
                ->sortByDesc('runs')
                ->take(6)
                ->values(),

            // The last ten runs, newest first, as a tier each.
            'form' => $runs
                ->sortByDesc('RecordDate')
                ->take(10)
                ->map(fn ($run) => (object) [
                    'tier' => $this->tier($run->Rank),
                    'rank' => $run->Rank,
                    'map' => $run->MapName,
                    'category' => $run->CategoryName,
                    'time' => $run->Time,
                    'delta' => $run->Delta ?? null,
                    'date' => $run->RecordDate,
                    'mapUuid' => $run->MapUUID,
                    'categoryId' => $run->CategoryId,
                ])
                ->values(),
        ];
    }

    /** wr, podium, top10 or other: how good a rank is, as a name. */
    public function tier(int|string|null $rank): string
    {
        $rank = $rank === null ? null : (int) $rank;

        return match (true) {
            $rank === null => 'other',
            $rank === 1 => 'wr',
            $rank <= 3 => 'podium',
            $rank <= 10 => 'top10',
            default => 'other',
        };
    }

    /**
     * Two players over the maps and categories they have both run.
     *
     * @param  Collection<int, object>  $a
     * @param  Collection<int, object>  $b
     * @return array{
     *     rows: Collection<int, object>, common: int,
     *     aWins: int, bWins: int, ties: int,
     *     avgGap: int|null, aSync: float|null, bSync: float|null
     * }
     */
    public function versus(Collection $a, Collection $b): array
    {
        $key = fn ($run) => $run->MapUUID.'|'.$run->CategoryId;
        $theirs = $b->keyBy($key);

        $rows = $a
            ->filter(fn ($run) => $theirs->has($key($run)))
            ->map(function ($mine) use ($theirs, $key) {
                $other = $theirs->get($key($mine));
                $gap = (int) $mine->Time - (int) $other->Time;

                return (object) [
                    'mapUuid' => $mine->MapUUID,
                    'map' => $mine->MapName,
                    'categoryId' => $mine->CategoryId,
                    'category' => $mine->CategoryName,
                    'a' => $mine,
                    'b' => $other,
                    // Negative: the first player is faster.
                    'gap' => $gap,
                    'winner' => $gap < 0 ? 'a' : ($gap > 0 ? 'b' : 'tie'),
                ];
            })
            ->sortBy([['map', 'asc'], ['category', 'asc']])
            ->values();

        $synced = $rows->filter(fn ($row) => ($row->a->Sync ?? null) !== null && ($row->b->Sync ?? null) !== null);

        return [
            'rows' => $rows,
            'common' => $rows->count(),
            'aWins' => $rows->where('winner', 'a')->count(),
            'bWins' => $rows->where('winner', 'b')->count(),
            'ties' => $rows->where('winner', 'tie')->count(),
            'avgGap' => $rows->isEmpty() ? null : (int) round($rows->avg(fn ($row) => abs($row->gap))),
            'aSync' => $synced->isEmpty() ? null : (float) $synced->avg(fn ($row) => (float) $row->a->Sync),
            'bSync' => $synced->isEmpty() ? null : (float) $synced->avg(fn ($row) => (float) $row->b->Sync),
        ];
    }

    /**
     * Medals awarded per map, summed. Cached like the rest of the profile data.
     *
     * @return array{gold: int, silver: int, bronze: int}
     */
    public function medals(string $userUuid): array
    {
        $totals = Cache::remember(
            "player_medals_{$userUuid}",
            now()->addMinutes(10),
            fn () => DB::connection('game_mysql')
                ->table('player_medals')
                ->selectRaw('COALESCE(SUM(gold), 0) AS gold, COALESCE(SUM(silver), 0) AS silver, COALESCE(SUM(bronze), 0) AS bronze')
                ->where('user_uuid', $userUuid)
                ->first()
        );

        return [
            'gold' => (int) ($totals->gold ?? 0),
            'silver' => (int) ($totals->silver ?? 0),
            'bronze' => (int) ($totals->bronze ?? 0),
        ];
    }
}
