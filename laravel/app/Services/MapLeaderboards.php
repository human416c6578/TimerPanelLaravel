<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The leaderboard of every category on one map, with each run's gap to the
 * record and the stats the timer recorded. Read-only, and cached per map: the
 * delete action in MapController::deleteMapRankedTime forgets the same key.
 */
class MapLeaderboards
{
    public static function cacheKey(string $mapUuid): string
    {
        return "map_leaderboards_{$mapUuid}";
    }

    /**
     * @return Collection<int|string, Collection<int, object>> runs grouped by category id
     */
    public function for(string $mapUuid): Collection
    {
        $rows = Cache::remember(
            self::cacheKey($mapUuid),
            now()->addMinutes(2),
            fn () => DB::connection('game_mysql')->select(
                <<<'SQL'
                WITH RankedRecords AS (
                    SELECT
                        RANK() OVER(PARTITION BY t.category_id ORDER BY t.`time`) AS `Rank`,
                        COUNT(*) OVER(PARTITION BY t.category_id) AS `Runs`,
                        MIN(t.`time`) OVER(PARTITION BY t.category_id) AS `BestTime`,
                        c.id AS CategoryId,
                        c.name AS CategoryName,
                        u.uuid AS UserUUID,
                        u.name AS UserName,
                        u.auth_id,
                        u.nationality,
                        t.`time`,
                        t.record_date,
                        t.start_speed,
                        t.jumps,
                        t.strafes,
                        t.`sync`,
                        t.overlaps,
                        t.overlaps_sd
                    FROM
                        times t
                    JOIN users u ON t.user_uuid = u.uuid
                    JOIN categories c ON t.category_id = c.id
                    WHERE
                        t.map_uuid = ?
                )
                SELECT
                    `Rank`,
                    `Runs`,
                    `time` - `BestTime` AS `Delta`,
                    CategoryId,
                    CategoryName,
                    UserUUID,
                    UserName,
                    auth_id,
                    nationality,
                    `time`,
                    record_date,
                    start_speed,
                    jumps,
                    strafes,
                    `sync`,
                    overlaps,
                    overlaps_sd
                FROM RankedRecords
                WHERE `Rank` <= 50
                ORDER BY CategoryName ASC, `Rank` ASC
                SQL,
                [$mapUuid]
            )
        );

        return collect($rows)->groupBy('CategoryId');
    }
}
