<?php

namespace App\Livewire;

use App\Services\CategoryRules;
use App\Services\LatestRuns;
use App\Services\SteamAvatars;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The home page's feed: the newest runs, a page at a time, filterable by
 * category, country and "records only". Paging and filtering both happen in
 * the query (see LatestRuns::page), so only the rows on screen are ever read.
 */
class LatestRunsFeed extends Component
{
    use WithPagination;

    #[Url(as: 'category', except: '')]
    public string $category = '';

    #[Url(as: 'country', except: '')]
    public string $country = '';

    #[Url(as: 'records', except: false)]
    public bool $recordsOnly = false;

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedCountry(): void
    {
        $this->resetPage();
    }

    public function updatedRecordsOnly(): void
    {
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'components.ui.livewire-pagination';
    }

    /** @return array{rows: Collection<int, object>, total: int} */
    #[Computed]
    public function data(): array
    {
        return app(LatestRuns::class)->page($this->getPage(), LatestRuns::PER_PAGE, $this->category, $this->country, $this->recordsOnly);
    }

    #[Computed]
    public function runs(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $this->data['rows'],
            $this->data['total'],
            LatestRuns::PER_PAGE,
            $this->getPage(),
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    /** @return Collection<int|string, string> category id => name */
    #[Computed]
    public function categories(): Collection
    {
        return app(CategoryRules::class)->all()->pluck('name', 'id')->sort();
    }

    /** @return Collection<int, string> */
    #[Computed]
    public function countries(): Collection
    {
        return app(LatestRuns::class)->countries();
    }

    /** @return array<string, string> */
    #[Computed]
    public function avatars(): array
    {
        return app(SteamAvatars::class)->small($this->data['rows']->pluck('auth_id')->unique());
    }

    public function clear(): void
    {
        $this->reset('category', 'country', 'recordsOnly');
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.latest-runs-feed');
    }
}
