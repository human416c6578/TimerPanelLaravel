<?php

namespace App\Livewire;

use App\Services\CategoryRules;
use App\Services\MapLeaderboards;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * One map's leaderboard: a tab per category, sortable on the stats the timer
 * records, with a head-to-head panel for any two runs.
 */
class MapLeaderboard extends Component
{
    #[Locked]
    public string $mapUuid;

    #[Locked]
    public string $mapName;

    #[Url(as: 'category', except: '')]
    public string $category = '';

    #[Url(except: 'rank')]
    public string $sort = 'rank';

    #[Url(except: 'asc')]
    public string $direction = 'asc';

    #[Url(except: false)]
    public bool $all = false;

    /** @var array<int, string> user uuids, at most two */
    public array $compare = [];

    /** Sortable column => property on the run object. */
    private const SORTS = [
        'rank' => 'Rank',
        'time' => 'time',
        'sync' => 'sync',
        'strafes' => 'strafes',
        'jumps' => 'jumps',
        'speed' => 'start_speed',
        'date' => 'record_date',
    ];

    public function mount(string $mapUuid, string $mapName): void
    {
        $this->mapUuid = $mapUuid;
        $this->mapName = $mapName;
    }

    public function selectCategory(string $categoryId): void
    {
        if ($this->boards->has($categoryId)) {
            $this->category = $categoryId;
            $this->compare = [];
        }
    }

    public function sortBy(string $column): void
    {
        if (! array_key_exists($column, self::SORTS)) {
            return;
        }

        // Stats read best high (sync) or low (time, strafes), but the first
        // click should always show the leaders, so only rank/time/date default to ascending.
        $this->direction = $this->sort === $column
            ? ($this->direction === 'asc' ? 'desc' : 'asc')
            : (in_array($column, ['sync', 'speed', 'jumps', 'date'], true) ? 'desc' : 'asc');
        $this->sort = $column;
    }

    public function toggleCompare(string $userUuid): void
    {
        if (in_array($userUuid, $this->compare, true)) {
            $this->compare = array_values(array_diff($this->compare, [$userUuid]));

            return;
        }

        // A third pick replaces the oldest instead of silently doing nothing.
        $this->compare = array_slice([...$this->compare, $userUuid], -2);
    }

    public function clearCompare(): void
    {
        $this->compare = [];
    }

    /**
     * Computed, so the cache store is read once per request rather than once
     * per tab, table and comparison.
     *
     * @return Collection<int|string, Collection<int, object>>
     */
    #[Computed]
    public function boards(): Collection
    {
        return app(MapLeaderboards::class)->for($this->mapUuid);
    }

    /** @return Collection<int, array{id: string, name: string, runs: int}> */
    #[Computed]
    public function categories(): Collection
    {
        return $this->boards
            ->map(fn ($runs, $id) => [
                'id' => (string) $id,
                'name' => $runs->first()->CategoryName,
                'runs' => (int) $runs->first()->Runs,
            ])
            ->values();
    }

    #[Computed]
    public function currentCategory(): ?string
    {
        $ids = $this->categories->pluck('id');

        return $ids->contains($this->category) ? $this->category : $ids->first();
    }

    /** @return Collection<int, object> */
    #[Computed]
    public function records(): Collection
    {
        $runs = $this->boards->get($this->currentCategory, collect());
        $property = self::SORTS[$this->sort] ?? 'Rank';
        $descending = $this->direction === 'desc';

        // Runs without a stat (older rows have no sync or strafes) always sink
        // to the bottom, whichever way the column is sorted.
        $sorted = $runs->sort(function ($a, $b) use ($property, $descending) {
            $left = $a->{$property};
            $right = $b->{$property};

            if ($left === null || $right === null) {
                return ($left === null) <=> ($right === null);
            }

            return $descending ? $right <=> $left : $left <=> $right;
        })->values();

        return $this->all || $this->sort !== 'rank' ? $sorted : $sorted->take(15);
    }

    /** @return Collection<int, object> the runs picked for the head-to-head, in pick order */
    #[Computed]
    public function compared(): Collection
    {
        $runs = $this->boards->get($this->currentCategory, collect())->keyBy('UserUUID');

        return collect($this->compare)->map(fn ($uuid) => $runs->get($uuid))->filter()->values();
    }

    #[Computed]
    public function record(): ?object
    {
        return $this->boards->get($this->currentCategory, collect())->firstWhere('Rank', 1);
    }

    /** @return array<int, array{label: string, value: string}> */
    #[Computed]
    public function rules(): array
    {
        return app(CategoryRules::class)->chips($this->currentCategory);
    }

    public function render(): View
    {
        return view('livewire.map-leaderboard');
    }
}
