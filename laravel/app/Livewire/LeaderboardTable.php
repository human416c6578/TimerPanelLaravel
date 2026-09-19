<?php

namespace App\Livewire;

use App\Services\Leaderboards;
use App\Services\SteamAvatars;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The leaderboard: one table, every measure a column, any column can order it,
 * and it pages through every player.
 */
class LeaderboardTable extends Component
{
    use WithPagination;

    #[Url(except: 'score')]
    public string $sort = 'score';

    public function sortBy(string $column): void
    {
        if (in_array($column, Leaderboards::SORTS, true)) {
            $this->sort = $column;
            $this->resetPage();
        }
    }

    public function paginationView(): string
    {
        return 'components.ui.livewire-pagination';
    }

    public function render(Leaderboards $leaderboards, SteamAvatars $steam): View
    {
        $page = $leaderboards->page($this->sort, $this->getPage(), Leaderboards::PER_PAGE);

        $entries = new LengthAwarePaginator(
            $page['entries'],
            $page['total'],
            Leaderboards::PER_PAGE,
            $this->getPage(),
            ['path' => request()->url(), 'pageName' => 'page']
        );

        return view('livewire.leaderboard-table', [
            'entries' => $entries,
            'avatars' => $steam->small($page['entries']->pluck('auth_id')),
        ]);
    }
}
