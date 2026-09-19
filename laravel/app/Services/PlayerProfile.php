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
