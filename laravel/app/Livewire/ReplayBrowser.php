<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ReplayBrowser extends Component
{
    use WithPagination;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(except: 'map')]
    public string $sort = 'map';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    private const SORTS = [
        'map' => 'm.name',
        'category' => 'c.name',
        'player' => 'u.name',
        'time' => 'rt.time',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if (! array_key_exists($column, self::SORTS)) {
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

    public function render(): View
    {
        $search = trim($this->search);

        // The same query ReplayController@index used: the record on every
        // map/category, which is the only run that has a recording.
        $replays = DB::connection('game_mysql')
            ->table('ranked_times as rt')
            ->join('maps as m', 'm.uuid', '=', 'rt.map_uuid')
            ->join('categories as c', 'c.id', '=', 'rt.category_id')
            ->join('users as u', 'u.uuid', '=', 'rt.user_uuid')
            ->where('rt.rank', 1)
            ->select([
                'rt.map_uuid',
                'rt.category_id',
                'rt.time',
                'm.name as map_name',
                'c.name as category_name',
                'u.uuid as user_uuid',
                'u.name as user_name',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('m.name', 'like', "%{$search}%")
                        ->orWhere('c.name', 'like', "%{$search}%")
                        ->orWhere('u.name', 'like', "%{$search}%");
                });
            })
            ->orderBy(self::SORTS[$this->sort], $this->direction)
            ->orderBy('m.name')
            ->paginate(25);

        return view('livewire.replay-browser', ['replays' => $replays]);
    }
}
