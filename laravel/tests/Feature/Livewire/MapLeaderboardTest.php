<?php

use App\Livewire\MapLeaderboard;
use App\Services\MapLeaderboards;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * The component reads its runs through MapLeaderboards, which is cache-backed.
 * Seeding the cache exercises the component without the game database.
 */
function boardRun(array $overrides = []): object
{
    return (object) array_merge([
        'Rank' => 1, 'Runs' => 3, 'Delta' => 0,
        'CategoryId' => 1, 'CategoryName' => 'Normal',
        'UserUUID' => 'u1', 'UserName' => 'Alpha', 'auth_id' => 'STEAM_0:0:1', 'nationality' => null,
        'time' => 60000, 'record_date' => '2026-01-01 10:00:00',
        'start_speed' => 300, 'jumps' => 100, 'strafes' => 300, 'sync' => 90.0,
        'overlaps' => 4, 'overlaps_sd' => 0.5,
    ], $overrides);
}

beforeEach(function () {
    Cache::put('category_rules', collect());

    Cache::put(MapLeaderboards::cacheKey('map-1'), [
        boardRun(['Rank' => 1, 'UserUUID' => 'u1', 'UserName' => 'Alpha', 'time' => 60000, 'sync' => 80.0, 'strafes' => 300]),
        boardRun(['Rank' => 2, 'UserUUID' => 'u2', 'UserName' => 'Bravo', 'time' => 65000, 'Delta' => 5000, 'sync' => 92.5, 'strafes' => 250]),
        boardRun(['Rank' => 3, 'UserUUID' => 'u3', 'UserName' => 'Charlie', 'time' => 70000, 'Delta' => 10000, 'sync' => null, 'strafes' => null, 'jumps' => null]),
        boardRun(['Rank' => 1, 'CategoryId' => 2, 'CategoryName' => 'Sideways', 'UserUUID' => 'u4', 'UserName' => 'Delta Force', 'time' => 99000]),
    ], 600);
});

function leaderboard(): Testable
{
    return Livewire::test(MapLeaderboard::class, ['mapUuid' => 'map-1', 'mapName' => 'bhop_test']);
}

test('it opens on the first category, ranked by time', function () {
    leaderboard()
        ->assertSeeInOrder(['Alpha', 'Bravo', 'Charlie'])
        ->assertDontSee('Delta Force');
});

test('switching category shows that category\'s runs', function () {
    leaderboard()
        ->call('selectCategory', '2')
        ->assertSee('Delta Force')
        ->assertDontSee('Bravo');
});

test('an unknown category id is ignored', function () {
    leaderboard()
        ->call('selectCategory', '999')
        ->assertSee('Alpha');
});

test('sorting by sync puts the best sync first and runs without one last', function () {
    leaderboard()
        ->call('sortBy', 'sync')
        ->assertSet('direction', 'desc')
        ->assertSeeInOrder(['Bravo', 'Alpha', 'Charlie']);
});

test('runs without a stat stay last when the sort is reversed', function () {
    leaderboard()
        ->call('sortBy', 'sync')
        ->call('sortBy', 'sync')
        ->assertSet('direction', 'asc')
        ->assertSeeInOrder(['Alpha', 'Bravo', 'Charlie']);
});

test('a column that is not sortable is ignored', function () {
    leaderboard()
        ->call('sortBy', 'password')
        ->assertSet('sort', 'rank');
});

test('a run with no recorded stats renders dashes, not zeros', function () {
    leaderboard()->assertSee('—');
});

test('at most two runs are compared and a third replaces the oldest', function () {
    leaderboard()
        ->call('toggleCompare', 'u1')
        ->call('toggleCompare', 'u2')
        ->call('toggleCompare', 'u3')
        ->assertSet('compare', ['u2', 'u3']);
});

test('picking a run twice removes it from the comparison', function () {
    leaderboard()
        ->call('toggleCompare', 'u1')
        ->call('toggleCompare', 'u1')
        ->assertSet('compare', []);
});

test('two picked runs open the head to head with the gap between them', function () {
    leaderboard()
        ->call('toggleCompare', 'u1')
        ->call('toggleCompare', 'u2')
        ->assertSee('Head to head')
        ->assertSee('5.000');
});

test('vs WR pits a run against the record in one call', function () {
    leaderboard()
        ->call('compareWithRecord', 'u3')
        ->assertSet('compare', ['u1', 'u3']);
});

test('the record holder cannot be compared with themselves', function () {
    leaderboard()
        ->call('compareWithRecord', 'u1')
        ->assertSet('compare', []);
});

test('the comparison is kept in the address so it can be shared', function () {
    Livewire::withQueryParams(['vs' => ['u1', 'u2']])
        ->test(MapLeaderboard::class, ['mapUuid' => 'map-1', 'mapName' => 'bhop_test'])
        ->assertSet('compare', ['u1', 'u2'])
        ->assertSee('Head to head');
});

test('the movement view shows the columns the summary hides', function () {
    leaderboard()
        ->assertDontSee('Overlaps')
        ->call('setView', 'movement')
        ->assertSee('Overlaps')
        ->assertSee('Strafes');
});

test('an unknown view falls back to the summary', function () {
    leaderboard()
        ->call('setView', 'nonsense')
        ->assertSet('view', 'summary');
});

test('the best sync on the category is boxed', function () {
    // Bravo has 92.5, the best in the fixture; Alpha's 80.0 is not.
    leaderboard()
        ->assertSeeHtml('title="Best on this category">92.5%')
        ->assertDontSeeHtml('title="Best on this category">80.0%');
});

test('the map cannot be changed from the browser', function () {
    leaderboard()->set('mapUuid', 'another-map');
})->throws(CannotUpdateLockedPropertyException::class);
