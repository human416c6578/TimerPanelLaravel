<?php

namespace App\Livewire;

use App\Models\Map;
use App\Services\MapImages;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Admin only: give a map its picture. Custom bhop and deathrun maps have no
 * picture anywhere else to fetch, so this is the way one gets there.
 */
class MapImageUpload extends Component
{
    use WithFileUploads;

    public string $map = '';

    public $photo;

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
    }

    /** @return array<int, string> every map name, for the picker */
    #[Computed]
    public function maps(): array
    {
        return Cache::remember('map_names', now()->addMinutes(10), fn () => Map::orderBy('name')->pluck('name')->all());
    }

    public function save(MapImages $images): void
    {
        // Livewire actions are public endpoints: check who is calling every time.
        abort_unless(auth()->check(), 403);

        $this->validate([
            'map' => ['required', 'string', 'max:64'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ]);

        // Only a map that exists may be given a picture.
        if (! in_array($this->map, $this->maps, true)) {
            $this->addError('map', 'That is not one of the maps.');

            return;
        }

        if ($images->save($this->map, file_get_contents($this->photo->getRealPath())) === null) {
            $this->addError('photo', 'That file is not a usable picture.');

            return;
        }

        session()->flash('status', "Saved the picture for {$this->map}.");
        $this->reset('photo');
    }

    public function remove(MapImages $images, string $name): void
    {
        abort_unless(auth()->check(), 403);

        $images->forget($name);
    }

    public function render(MapImages $images): View
    {
        return view('livewire.map-image-upload', ['stored' => $images->count()]);
    }
}
