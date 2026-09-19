<x-ui.panel>
    <header class="panel-header"><p class="hud-label"><x-icon name="map" class="size-3.5" /> Map pictures</p></header>

    <div class="space-y-3 p-4">
        <p class="text-[12px] text-muted">
            {{ $stored }} {{ Str::plural('map', $stored) }} with a picture. The rest show a generated cover.
            Custom maps have no picture anywhere else, so add one here: JPG, PNG or WebP, up to 2&nbsp;MB.
        </p>

        <form wire:submit="save" class="space-y-2">
            <div>
                <label for="map-pick" class="sr-only">Map</label>
                <select id="map-pick" wire:model="map" class="input">
                    <option value="">Choose a map…</option>
                    @foreach ($this->maps as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
                @error('map') <p class="mt-1 text-[12px] text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="map-photo" class="sr-only">Picture</label>
                <input id="map-photo" type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="input file:mr-3 file:rounded file:border-0 file:bg-surface-3 file:px-2 file:py-1 file:text-[12px] file:text-ink">
                @error('photo') <p class="mt-1 text-[12px] text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="save,photo">Save picture</button>

                @if ($map !== '')
                    <button type="button" class="btn btn-ghost btn-sm" wire:click="remove('{{ $map }}')" wire:confirm="Remove the picture for {{ $map }}?">Remove</button>
                @endif

                <span wire:loading wire:target="photo" class="text-[11px] text-subtle">uploading…</span>
            </div>

            @if (session('status'))
                <p class="text-[12px] font-semibold text-live">{{ session('status') }}</p>
            @endif
        </form>
    </div>
</x-ui.panel>
