<?php

namespace App\Livewire;

use App\Services\PlayerProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * A player's runs, sortable on rank, time, gap to the record and the recorded
 * stats. The data is the cached collection the profile summary also reads.
 */
class PlayerRecords extends Component
{
    use WithPagination;

    #[Locked]
    public string $userUuid;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'date')]
    public string $sort = 'date';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    private const SORTS = [
        'rank' => 'Rank',
        'map' => 'MapName',
        'category' => 'CategoryName',
        'time' => 'Time',
        'delta' => 'Delta',
        'sync' => 'Sync',
        'date' => 'RecordDate',
    ];

    private const PER_PAGE = 15;

    public function mount(string $userUuid): void
    {
        $this->userUuid = $userUuid;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if (! array_key_exists($column, self::SORTS)) {
            return;
        }

        $this->direction = $this->sort === $column && $this->direction === 'asc'
            ? 'desc'
            : (in_array($column, ['sync', 'date'], true) && $this->sort !== $column ? 'desc' : 'asc');
        $this->sort = $column;
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'components.ui.livewire-pagination';
    }

    public function render(PlayerProfile $profile): View
    {
        $runs = $profile->runs($this->userUuid);
        $search = mb_strtolower(trim($this->search));

        if ($search !== '') {
            $runs = $runs->filter(fn ($run) => str_contains(mb_strtolower($run->MapName), $search)
                || str_contains(mb_strtolower($run->CategoryName), $search));
        }

        $property = self::SORTS[$this->sort] ?? 'RecordDate';
        $descending = $this->direction === 'desc';

        $runs = $runs->sort(function ($a, $b) use ($property, $descending) {
            $left = $a->{$property};
            $right = $b->{$property};

            // Rows without the stat (old runs have no sync) sink to the bottom.
            if ($left === null || $right === null) {
                return ($left === null) <=> ($right === null);
            }

            return $descending ? $right <=> $left : $left <=> $right;
        })->values();

        $page = $this->getPage();

        $paginator = new LengthAwarePaginator(
            $runs->forPage($page, self::PER_PAGE)->values(),
            $runs->count(),
            self::PER_PAGE,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );

        return view('livewire.player-records', ['runs' => $paginator]);
    }
}
