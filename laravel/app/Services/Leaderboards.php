<?php

namespace App\Services;

use App\Models\GameUser;
use App\Models\PlayedTime;
use App\Models\Ranking;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * One leaderboard with every measure in its own column: time on the server,
 * medals, world records and the score.
 *
 * It is read a page at a time. The column the board is ordered by decides who
 * is on the page (ORDER BY … LIMIT … OFFSET … in the query); the other columns
 * are then looked up for just those players, so nothing ever reads more than a
 * page of them, however many players there are. Read-only, cached per page.
 */
class Leaderboards
{
    public const PER_PAGE = 25;

    /** The columns a board can be ordered by. */
    public const SORTS = ['score', 'played', 'records', 'gold', 'silver', 'bronze'];

    public static function pageKey(string $sort, int $page, int $perPage): string
    {
        return "leaderboard_{$sort}_{$page}_{$perPage}";
    }

    /**
     * @return array{entries: Collection<int, object>, total: int}
     */
    public function page(string $sort = 'score', int $page = 1, int $perPage = self::PER_PAGE): array
    {
        $sort = in_array($sort, self::SORTS, true) ? $sort : 'score';
        $page = max(1, $page);

        return Cache::remember(self::pageKey($sort, $page, $perPage), now()->addMinutes(10), function () use ($sort, $page, $perPage) {
            $uuids = $this->leaders($sort, ($page - 1) * $perPage, $perPage);

            return ['entries' => $this->entries($uuids), 'total' => $this->total($sort)];
        });
    }

    /**
     * How many players the board has. Counted once for ten minutes per ordering.
     */
    private function total(string $sort): int
    {
        return (int) Cache::remember("leaderboard_total_{$sort}", now()->addMinutes(10), fn () => match ($sort) {
            'records' => DB::connection('game_mysql')->table('ranked_times')->where('rank', 1)->distinct()->count('user_uuid'),
            'played' => PlayedTime::distinct()->count('auth_id'),
            default => Ranking::count(),
        });
    }

    /** @return Collection<int, string> the player uuids on this page, best first */
    private function leaders(string $sort, int $offset, int $limit): Collection
    {
        return match ($sort) {
            'records' => DB::connection('game_mysql')->table('ranked_times')
                ->select('user_uuid')
                ->where('rank', 1)
                ->groupBy('user_uuid')
                ->orderByRaw('COUNT(*) DESC')
                ->orderBy('user_uuid')
                ->offset($offset)->limit($limit)
                ->pluck('user_uuid'),

            // Played time is keyed by SteamID, so go through the players table. The
            // page of SteamIDs runs first and on its own: MySQL will not take a
            // LIMIT inside an IN (...) subquery.
            'played' => $this->orderByPlayed(
                GameUser::whereIn('auth_id', PlayedTime::select('auth_id')
                    ->groupBy('auth_id')
                    ->orderByRaw('SUM(time_played) DESC')
                    ->orderBy('auth_id')
                    ->offset($offset)->limit($limit)
                    ->pluck('auth_id'))
                    ->pluck('uuid')
            ),

            default => Ranking::orderByDesc($sort)->orderByDesc('score')->orderBy('user_uuid')
                ->offset($offset)->limit($limit)
                ->pluck('user_uuid'),
        };
    }

    /**
     * Fill in every column for one page of players.
     *
     * @param  Collection<int, string>  $uuids
     * @return Collection<int, object>
     */
    private function entries(Collection $uuids): Collection
    {
        if ($uuids->isEmpty()) {
            return collect();
        }

        $users = GameUser::whereIn('uuid', $uuids)->get(['uuid', 'name', 'auth_id', 'nationality'])->keyBy('uuid');
        $ranking = Ranking::whereIn('user_uuid', $uuids)->get()->keyBy('user_uuid');

        $records = DB::connection('game_mysql')->table('ranked_times')
            ->selectRaw('user_uuid, COUNT(*) as total')
            ->where('rank', 1)->whereIn('user_uuid', $uuids)
            ->groupBy('user_uuid')
            ->pluck('total', 'user_uuid');

        $played = $this->playedTotals($users->pluck('auth_id'));

        return $uuids
            ->filter(fn ($uuid) => $users->has($uuid))
            ->map(fn ($uuid) => (object) [
                'name' => $users[$uuid]->name,
                'uuid' => $uuid,
                'auth_id' => $users[$uuid]->auth_id,
                'nationality' => $users[$uuid]->nationality,
                'played' => (int) ($played[$users[$uuid]->auth_id] ?? 0),
                'records' => (int) ($records[$uuid] ?? 0),
                'gold' => (int) ($ranking[$uuid]->gold ?? 0),
                'silver' => (int) ($ranking[$uuid]->silver ?? 0),
                'bronze' => (int) ($ranking[$uuid]->bronze ?? 0),
                'score' => (int) ($ranking[$uuid]->score ?? 0),
            ])
            ->values();
    }

    /**
     * whereIn does not keep the page's order, so put the players back in order
     * of time played.
     *
     * @param  Collection<int, string>  $uuids
     * @return Collection<int, string>
     */
    private function orderByPlayed(Collection $uuids): Collection
    {
        $users = GameUser::whereIn('uuid', $uuids)->get(['uuid', 'auth_id']);
        $totals = $this->playedTotals($users->pluck('auth_id'));

        return $users->sortByDesc(fn ($user) => (int) ($totals[$user->auth_id] ?? 0))->pluck('uuid')->values();
    }

    /**
     * Seconds played across every server, per SteamID.
     *
     * @param  Collection<int, string>  $authIds
     * @return Collection<string, int>
     */
    private function playedTotals(Collection $authIds): Collection
    {
        return PlayedTime::selectRaw('auth_id, SUM(time_played) as total')
            ->whereIn('auth_id', $authIds)
            ->groupBy('auth_id')
            ->pluck('total', 'auth_id');
    }
}
