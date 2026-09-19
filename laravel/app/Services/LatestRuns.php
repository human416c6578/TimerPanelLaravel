<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The newest runs, read one page at a time.
 *
 * The page is cut in SQL (ORDER BY … LIMIT … OFFSET …) *before* anything is
 * joined, so the database only ever looks up the users, maps and ranks of the
 * dozen rows on screen, however long the history is. Filters are applied in
 * the same query rather than over a downloaded list.
 *
 * Read-only, and cached: a page for a minute, its total for five.
 */
class LatestRuns
{
    public const PER_PAGE = 12;

    /** The last day's newest runs, for the summary cards beside the feed. */
    public const WINDOW_KEY = 'latest_times_24h';

    public static function pageKey(int $page, int $perPage, string $category, string $country, bool $recordsOnly): string
    {
        return 'latest_runs_page_'.md5(implode('|', [$page, $perPage, $category, strtolower($country), (int) $recordsOnly]));
    }

    /**
     * One page of the feed.
     *
     * @return array{rows: Collection<int, object>, total: int}
     */
    public function page(int $page, int $perPage = self::PER_PAGE, string $category = '', string $country = '', bool $recordsOnly = false): array
    {
        $page = max(1, $page);

        return Cache::remember(
            self::pageKey($page, $perPage, $category, $country, $recordsOnly),
            now()->addSeconds(60),
            function () use ($page, $perPage, $category, $country, $recordsOnly) {
                $filtered = fn () => $this->filtered($category, $country, $recordsOnly);

                $total = Cache::remember(
                    'latest_runs_total_'.md5("{$category}|".strtolower($country).'|'.(int) $recordsOnly),
                    now()->addMinutes(5),
                    fn () => (int) $filtered()->count()
                );

                $slice = $filtered()
                    ->select('t.time', 't.record_date', 't.start_speed', 't.user_uuid', 't.map_uuid', 't.category_id')
                    // Same direction on both: a mixed DESC/ASC sort cannot use an index on
                    // (record_date, user_uuid) and turns into a full scan and filesort.
                    ->orderByDesc('t.record_date')
                    ->orderByDesc('t.user_uuid')
                    ->offset(($page - 1) * $perPage)
                    ->limit($perPage);

                return ['rows' => $this->decorate($slice), 'total' => $total];
            }
        );
    }

    /**
     * The newest 50 runs of the last 24 hours, for the cards beside the feed.
     *
     * @return Collection<int, object>
     */
    public function window(): Collection
    {
        return Cache::remember(self::WINDOW_KEY, now()->addMinutes(2), function () {
            $recent = DB::connection('game_mysql')
                ->table('times as t')
                ->select('t.time', 't.record_date', 't.start_speed', 't.user_uuid', 't.map_uuid', 't.category_id')
                ->where('t.record_date', '>', DB::raw('NOW() - INTERVAL 1 DAY'))
                ->orderByDesc('t.record_date')
                ->limit(50);

            return $this->decorate($recent);
        });
    }

    /** Countries that have players, for the filter. */
    public function countries(): Collection
    {
        return Cache::remember('latest_runs_countries', now()->addHour(), fn () => DB::connection('game_mysql')
            ->table('users')
            ->whereNotNull('nationality')
            ->where('nationality', '!=', '')
            ->distinct()
            ->orderBy('nationality')
            ->pluck('nationality')
            ->map(fn ($code) => strtolower($code))
            ->filter(fn ($code) => preg_match('/^[a-z]{2}$/', $code))
            ->unique()
            ->values());
    }

    private function filtered(string $category, string $country, bool $recordsOnly)
    {
        return DB::connection('game_mysql')->table('times as t')
            ->when($category !== '', fn ($query) => $query->where('t.category_id', (int) $category))
            ->when($country !== '', fn ($query) => $query
                ->join('users as f_user', 'f_user.uuid', '=', 't.user_uuid')
                ->where('f_user.nationality', $country))
            ->when($recordsOnly, fn ($query) => $query
                ->join('ranked_times as f_rank', function ($join) {
                    $join->on('f_rank.user_uuid', '=', 't.user_uuid')
                        ->on('f_rank.map_uuid', '=', 't.map_uuid')
                        ->on('f_rank.category_id', '=', 't.category_id')
                        ->where('f_rank.rank', '=', 1);
                }));
    }

    /**
     * Give a slice of `times` everything a row shows: who, where, its rank, and
     * the record to measure it against. Only the slice is joined.
     *
     * @return Collection<int, object>
     */
    private function decorate($slice): Collection
    {
        return DB::connection('game_mysql')
            ->query()
            ->fromSub($slice, 't')
            ->select(
                't.time',
                't.record_date',
                't.start_speed',
                't.user_uuid',
                't.map_uuid',
                't.category_id',
                'u.name as user_name',
                'u.auth_id',
                'u.nationality',
                'm.name as map_name',
                'c.name as category_name',
                'rt.rank',
                'best.time as best_time',
                // Computed by the database against its own NOW(): record_date is in
                // that server's timezone, which need not be this application's.
                DB::raw('TIMESTAMPDIFF(SECOND, t.record_date, NOW()) as age_seconds'),
            )
            ->join('users as u', 'u.uuid', '=', 't.user_uuid')
            ->join('maps as m', 'm.uuid', '=', 't.map_uuid')
            ->join('categories as c', 'c.id', '=', 't.category_id')
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
            ->orderByDesc('t.record_date')
            ->orderByDesc('t.user_uuid')
            ->get()
            // Remember when this was read, so the age can keep counting while it is cached.
            ->each(fn ($run) => $run->cached_at = time());
    }
}
