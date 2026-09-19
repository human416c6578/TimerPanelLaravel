<?php

namespace App\Livewire;

use App\Models\GameUser;
use App\Services\SteamAvatars;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PlayerDirectory extends Component
{
    use WithPagination;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(except: 'name')]
    public string $sort = 'name';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, ['name', 'auth_id'], true)) {
            return;
        }

        $this->direction = $this->sort === $column && $this->direction === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'components.ui.livewire-pagination';
    }

    private const PER_PAGE = 20;

    public function render(): View
    {
        $search = trim($this->search);
        $page = $this->getPage();

        // A page of the directory, and its total, cached for two minutes per
        // search, ordering and page. A search is LIKE '%term%', which no index
        // can serve, so it scans the players table twice (rows, then the count for
        // the pager); paging through the same search must not pay that again.
        $data = Cache::remember(
            'players_directory_'.md5(implode('|', [$search, $this->sort, $this->direction, $page])),
            now()->addMinutes(2),
            function () use ($search, $page) {
                $matching = GameUser::query()->when($search !== '', function ($query) use ($search) {
                    // Grouped, so the OR cannot escape any later constraint.
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('auth_id', 'like', "%{$search}%");
                    });
                });

                return [
                    'rows' => (clone $matching)
                        ->select('name', 'auth_id', 'uuid', 'nationality')
                        ->orderBy($this->sort, $this->direction)
                        ->orderBy('uuid')
                        ->offset(($page - 1) * self::PER_PAGE)
                        ->limit(self::PER_PAGE)
                        ->get(),
                    'total' => (clone $matching)->count(),
                ];
            }
        );

        $players = new LengthAwarePaginator($data['rows'], $data['total'], self::PER_PAGE, $page, ['path' => request()->url(), 'pageName' => 'page']);

        return view('livewire.player-directory', [
            'players' => $players,
            // One request for the whole page of twenty, cached per player.
            'avatars' => app(SteamAvatars::class)->small($data['rows']->pluck('auth_id')),
        ]);
    }
}
