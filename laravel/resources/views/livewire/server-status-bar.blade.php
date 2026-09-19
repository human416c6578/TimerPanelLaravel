<div>
    <div class="mb-2 flex items-center justify-between">
        <p class="hud-label"><x-icon name="server" class="size-3.5" /> Game servers</p>
        <p class="text-[11px] text-subtle">
            <span @class(['font-bold', 'text-live' => $online->isNotEmpty()])>{{ $online->sum(fn ($server) => (int) $server['players']) }}</span>
            players online
        </p>
    </div>

    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($servers as $server)
            @php
                $max = max(1, (int) ($server['max_players'] ?? 1));
                $fill = $server['online'] ? min(100, round(((int) $server['players'] / $max) * 100)) : 0;
            @endphp

            <div class="panel px-3 py-2.5" wire:key="srv-{{ $loop->index }}">
                <div class="flex items-center gap-2">
                    <span @class(['dot', 'dot-live' => $server['online']])></span>
                    <span class="truncate text-[13px] font-bold">{{ $server['name'] }}</span>

                    <span class="ms-auto shrink-0 text-[12px] font-semibold tabular {{ $server['online'] ? '' : 'text-subtle' }}">
                        {{ $server['online'] ? $server['players'].'/'.$server['max_players'] : 'offline' }}
                    </span>
                </div>

                <span class="slots mt-2" role="img" aria-label="{{ $fill }}% full"><span class="slots-fill" style="width: {{ $fill }}%"></span></span>

                <div class="mt-2 flex items-center justify-between gap-2">
                    <span class="truncate font-mono text-[11px] {{ $server['online'] ? 'text-muted' : 'text-subtle' }}">{{ $server['map'] ?? '—' }}</span>

                    @if ($server['online'])
                        <a href="steam://connect/{{ $server['host'] }}:{{ $server['port'] }}" class="shrink-0 text-[11px] font-bold uppercase tracking-wider text-accent hover:underline" title="Join {{ $server['name'] }}">
                            Connect
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
