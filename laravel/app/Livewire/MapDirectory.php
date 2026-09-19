<?php

namespace App\Livewire;

use App\Models\Map;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class MapDirectory extends Component
{
    use WithPagination;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    /** all | bhop | deathrun */
    #[Url(except: 'all')]
    public string $mode = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setMode(string $mode): void
    {
        $this->mode = in_array($mode, ['all', 'bhop', 'deathrun'], true) ? $mode : 'all';
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'components.ui.livewire-pagination';
    }

    public function render(): View
    {
        $search = trim($this->search);

        $maps = Map::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            // The timer has no "mode" column: deathrun maps are the ones named for it.
            ->when($this->mode === 'deathrun', fn ($query) => $query->where('name', 'like', '%deathrun%'))
            ->when($this->mode === 'bhop', fn ($query) => $query->where('name', 'not like', '%deathrun%'))
            ->orderBy('name')
            ->paginate(30);

        return view('livewire.map-directory', ['maps' => $maps]);
    }
}
