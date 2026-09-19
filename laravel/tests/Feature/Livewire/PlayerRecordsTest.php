<?php

use App\Livewire\PlayerRecords;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function recordRow(array $overrides = []): object
{
    return (object) array_merge([
        'Rank' => 1, 'Runs' => 5, 'MapUUID' => 'm1', 'MapName' => 'bhop_arcane',
        'CategoryName' => 'Normal', 'CategoryId' => 1, 'Time' => 64000,
        'RecordDate' => '2026-01-01 10:00:00', 'Delta' => 0, 'Sync' => 90.0,
        'BestTime' => 64000, 'BestUserUUID' => 'u1',
    ], $overrides);
}

beforeEach(function () {
    Cache::put('ranked_times_u1', collect([
        recordRow(['Rank' => 1, 'MapName' => 'bhop_arcane', 'MapUUID' => 'm1', 'Delta' => 0, 'Sync' => 90.0, 'RecordDate' => '2026-01-01']),
        recordRow(['Rank' => 4, 'MapName' => 'bhop_eazy', 'MapUUID' => 'm2', 'Delta' => 3000, 'Sync' => null, 'RecordDate' => '2026-03-01']),
        recordRow(['Rank' => 2, 'MapName' => 'deathrun_castle', 'MapUUID' => 'm3', 'Delta' => 800, 'Sync' => 75.5, 'RecordDate' => '2026-02-01']),
    ]), 600);
});

function records(): Testable
{
    return Livewire::test(PlayerRecords::class, ['userUuid' => 'u1']);
}

test('it lists newest first by default', function () {
    records()->assertSeeInOrder(['bhop_eazy', 'deathrun_castle', 'bhop_arcane']);
});

test('it filters by map name', function () {
    records()
        ->set('search', 'castle')
        ->assertSee('deathrun_castle')
        ->assertDontSee('bhop_arcane');
});

test('it filters by category name too', function () {
    Cache::put('ranked_times_u1', collect([
        recordRow(['MapName' => 'bhop_one', 'CategoryName' => 'Autobhop']),
        recordRow(['MapName' => 'bhop_two', 'CategoryName' => 'Normal']),
    ]), 600);

    records()->set('search', 'auto')->assertSee('bhop_one')->assertDontSee('bhop_two');
});

test('sorting by gap to the record ranks the closest first', function () {
    records()
        ->call('sortBy', 'delta')
        ->assertSeeInOrder(['bhop_arcane', 'deathrun_castle', 'bhop_eazy']);
});

test('a run with no sync always sorts last', function () {
    records()
        ->call('sortBy', 'sync')
        ->assertSeeInOrder(['bhop_arcane', 'deathrun_castle', 'bhop_eazy'])
        ->call('sortBy', 'sync')
        ->assertSeeInOrder(['deathrun_castle', 'bhop_arcane', 'bhop_eazy']);
});

test('a column that is not sortable is ignored', function () {
    records()->call('sortBy', 'password')->assertSet('sort', 'date');
});

test('the world records show as such and other runs show their gap', function () {
    records()
        ->assertSee('WR')
        ->assertSee('+0.800')
        ->assertSee('+3.000');
});

test('it pages through long histories', function () {
    Cache::put('ranked_times_u1', collect(range(1, 40))->map(
        fn ($n) => recordRow(['MapName' => sprintf('map_%02d', $n), 'MapUUID' => "m{$n}", 'RecordDate' => sprintf('2026-01-%02d', min($n, 28))])
    ), 600);

    records()
        ->assertSee('1–15 of 40')
        ->call('gotoPage', 3)
        ->assertSee('31–40 of 40');
});

test('only records get a Watch button', function () {
    records()
        ->assertSeeHtml('/runs/m1/1/u1?tab=replay')
        ->assertDontSeeHtml('/runs/m2/1/u1?tab=replay');
});

test('records only leaves just the records', function () {
    records()
        ->set('recordsOnly', true)
        ->assertSee('bhop_arcane')
        ->assertDontSee('bhop_eazy')
        ->assertDontSee('deathrun_castle');
});
