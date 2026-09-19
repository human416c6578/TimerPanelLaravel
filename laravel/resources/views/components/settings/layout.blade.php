<div class="flex items-start gap-8 max-md:flex-col">
    <nav class="w-full md:w-56">
        <flux:navlist>
            <flux:navlist.item :href="route('settings.profile')" :current="request()->routeIs('settings.profile')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.password')" :current="request()->routeIs('settings.password')" wire:navigate>{{ __('Password') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.appearance')" :current="request()->routeIs('settings.appearance')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
        </flux:navlist>
    </nav>

    <div class="min-w-0 flex-1">
        <h2 class="text-lg font-semibold">{{ $heading ?? '' }}</h2>
        <p class="mt-1 text-sm text-muted">{{ $subheading ?? '' }}</p>

        <div class="panel mt-5 w-full max-w-lg p-6">
            {{ $slot }}
        </div>
    </div>
</div>
