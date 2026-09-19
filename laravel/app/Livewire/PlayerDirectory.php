<?php

namespace App\Livewire;

use App\Models\GameUser;
use Illuminate\Contracts\View\View;
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

    public function render(): View
    {
        $search = trim($this->search);

        $players = GameUser::query()
            ->select('name', 'auth_id', 'uuid', 'nationality')
            ->when($search !== '', function ($query) use ($search) {
                // Grouped, so the OR cannot escape any later constraint.
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('auth_id', 'like', "%{$search}%");
                });
            })
            ->orderBy($this->sort, $this->direction)
            ->paginate(20);

        return view('livewire.player-directory', ['players' => $players]);
    }
}
