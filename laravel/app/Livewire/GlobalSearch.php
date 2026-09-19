<?php

namespace App\Livewire;

use App\Models\GameUser;
use App\Models\Map;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The search box in the header: players and maps in one place. With
 * $compareWith set it becomes a player picker whose results link to the
 * head-to-head page instead of the profile.
 */
class GlobalSearch extends Component
{
    public string $q = '';

    /** Player uuid to compare against; when set, only players are offered. */
    #[Locked]
    public ?string $compareWith = null;

    #[Locked]
    public string $placeholder = 'Search a player or a map';

    private const LIMIT = 6;

    public function mount(?string $compareWith = null, ?string $placeholder = null): void
    {
        $this->compareWith = $compareWith;

        if ($placeholder !== null) {
            $this->placeholder = $placeholder;
        }
    }

    /** @return Collection<int, object> */
    #[Computed]
    public function players(): Collection
    {
        $term = trim($this->q);

        if (mb_strlen($term) < 2) {
            return collect();
        }

        return GameUser::query()
            ->select('uuid', 'name', 'auth_id', 'nationality')
            ->when($this->compareWith, fn ($query) => $query->where('uuid', '!=', $this->compareWith))
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('auth_id', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get();
    }

    /** @return Collection<int, object> */
    #[Computed]
    public function maps(): Collection
    {
        $term = trim($this->q);

        if ($this->compareWith !== null || mb_strlen($term) < 2) {
            return collect();
        }

        return Map::query()
            ->where('name', 'like', "%{$term}%")
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.global-search');
    }
}
