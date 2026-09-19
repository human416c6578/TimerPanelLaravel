<?php

namespace App\Livewire;

use App\Services\CategoryRules;
use App\Services\MapLeaderboards;
use App\Services\SteamAvatars;
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

    /** Which columns to show: the headline ones, or the movement stats. */
    #[Url(except: 'summary')]
    public string $view = 'summary';

    /**
     * Uuids of the runs being compared, at most two. Kept in the URL, so a
     * head-to-head can be sent to someone as a link.
     *
     * @var array<int, string>
     */
    #[Url(as: 'vs', except: [])]
    public array $compare = [];

    /** Sortable column => property on the run object. */
    private const SORTS = [
        'rank' => 'Rank',
        'time' => 'time',
        'sync' => 'sync',
        'strafes' => 'strafes',
        'jumps' => 'jumps',
        'overlaps' => 'overlaps',
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

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['summary', 'movement'], true) ? $view : 'summary';
    }

    /** One click: this run against the record on the same category. */
    public function compareWithRecord(string $userUuid): void
    {
        $record = $this->record;

        if ($record === null || $record->UserUUID === $userUuid) {
            return;
        }

        $this->compare = [$record->UserUUID, $userUuid];
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

    /** @return array<string, string> auth_id => Steam picture, for the rows on screen */
    #[Computed]
    public function avatars(): array
    {
        return app(SteamAvatars::class)->small($this->records->pluck('auth_id')->unique());
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

    /**
     * The best value of each stat on this category, so its cell can be boxed.
     * Only stats where one direction is unambiguously better: more sync and a
     * higher start speed, fewer overlaps.
     *
     * @return array<string, float|int>
     */
    #[Computed]
    public function bests(): array
    {
        $runs = $this->boards->get($this->currentCategory, collect());

        $best = fn (string $property, string $direction) => $runs
            ->pluck($property)
            ->filter(fn ($value) => $value !== null)
            ->{$direction === 'max' ? 'max' : 'min'}();

        return array_filter([
            'sync' => $best('sync', 'max'),
            'start_speed' => $best('start_speed', 'max'),
            'overlaps' => $best('overlaps', 'min'),
            'overlaps_sd' => $best('overlaps_sd', 'min'),
        ], fn ($value) => $value !== null);
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
