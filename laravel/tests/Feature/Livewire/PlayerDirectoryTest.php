<?php

use App\Livewire\PlayerDirectory;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function directoryPlayer(string $name, string $code = 'ro'): object
{
    return (object) ['name' => $name, 'auth_id' => 'STEAM_0:0:1', 'uuid' => 'u-'.$name, 'nationality' => $code];
}

/** Stand in for the page the query would return: the component reads it through the cache. */
function seedDirectory(array $players, int $total, string $search = '', string $sort = 'name', string $direction = 'asc', int $page = 1): void
{
    Cache::put(
        'players_directory_'.md5(implode('|', [$search, $sort, $direction, $page])),
        ['rows' => collect($players), 'total' => $total],
        600
    );
}

beforeEach(function () {
    config(['services.steam.key' => null]);

    seedDirectory([directoryPlayer('Alpha'), directoryPlayer('Bravo')], 45);
});

function directory(): Testable
{
    return Livewire::test(PlayerDirectory::class);
}

test('it lists a page of players with their flag and profile link', function () {
    directory()->assertSee('Alpha')->assertSee('Bravo')->assertSeeHtml('/players/u-Alpha')->assertSeeHtml('flagcdn.com/16x12/ro.png');
});

test('twenty to a page: 45 players is three pages', function () {
    directory()->assertSee('of 45')->assertSeeHtml("gotoPage(3, 'page')")->assertDontSeeHtml("gotoPage(4, 'page')");
});

test('paging asks for the next page rather than filtering the first', function () {
    seedDirectory([directoryPlayer('Zulu')], 45, page: 2);

    directory()->assertDontSee('Zulu')->call('gotoPage', 2)->assertSee('Zulu')->assertDontSee('Alpha');
});

test('a search is a query of its own and starts again from page one', function () {
    seedDirectory([directoryPlayer('Kaze')], 1, search: 'kaz');
    seedDirectory([directoryPlayer('Zulu')], 45, page: 2);

    directory()
        ->call('gotoPage', 2)
        ->set('search', 'kaz')
        ->assertSee('Kaze')
        ->assertSet('paginators.page', 1)
        // one result is one page: no pager to show
        ->assertDontSeeHtml("gotoPage(2, 'page')");
});

test('reordering is a query of its own too', function () {
    seedDirectory([directoryPlayer('Zulu')], 45, sort: 'name', direction: 'desc');

    directory()->call('sortBy', 'name')->assertSet('direction', 'desc')->assertSee('Zulu');
});

test('only the columns it knows can be ordered', function () {
    directory()->call('sortBy', 'password')->assertSet('sort', 'name');
});

test('a search with no results says so', function () {
    seedDirectory([], 0, search: 'nobody');

    directory()->set('search', 'nobody')->assertSee('No players match that search.');
});
