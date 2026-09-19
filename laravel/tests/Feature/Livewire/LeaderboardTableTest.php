<?php

use App\Livewire\LeaderboardTable;
use App\Services\Leaderboards;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function lbEntry(string $name, array $overrides = []): object
{
    return (object) array_merge([
        'name' => $name, 'uuid' => "u-{$name}", 'auth_id' => 'STEAM_0:0:1', 'nationality' => null,
        'played' => 3600, 'records' => 0, 'gold' => 0, 'silver' => 0, 'bronze' => 0, 'score' => 0,
    ], $overrides);
}

function seedBoard(string $sort, array $entries, int $total, int $page = 1): void
{
    Cache::put(Leaderboards::pageKey($sort, $page, Leaderboards::PER_PAGE), ['entries' => collect($entries), 'total' => $total], 600);
}

beforeEach(function () {
    config(['services.steam.key' => null]);

    // The component reads through Leaderboards, which is cache-backed per ordering and page.
    seedBoard('score', [
        lbEntry('Alpha', ['score' => 900, 'gold' => 5, 'played' => 7200, 'records' => 3]),
        lbEntry('Bravo', ['score' => 400, 'silver' => 2]),
        lbEntry('Charlie', ['score' => 100, 'bronze' => 1]),
        lbEntry('Delta', ['score' => 50]),
    ], 4);

    seedBoard('played', [lbEntry('Zulu', ['played' => 99999]), lbEntry('Alpha', ['played' => 7200])], 2);
});

function board(): Testable
{
    return Livewire::test(LeaderboardTable::class);
}

test('it is one table with time played, medals, records and score as columns', function () {
    board()
        ->assertSee('Score')->assertSee('Time played')->assertSee('Records')
        ->assertSee('Gold')->assertSee('Silver')->assertSee('Bronze')
        ->assertSeeInOrder(['Alpha', 'Bravo', 'Charlie', 'Delta']);
});

test('the first three rows carry the podium treatment, the rest do not', function () {
    board()
        ->assertSeeHtml('lb-row lb-row-1')
        ->assertSeeHtml('lb-row lb-row-3')
        ->assertDontSeeHtml('lb-row-4');
});

test('ordering by another column re-ranks the players', function () {
    board()
        ->call('sortBy', 'played')
        ->assertSet('sort', 'played')
        ->assertSeeInOrder(['Zulu', 'Alpha'])
        ->assertDontSee('Bravo');
});

test('a column that is not a measure is ignored', function () {
    board()->call('sortBy', 'password')->assertSet('sort', 'score');
});

test('a long board pages through every player and keeps counting the ranks', function () {
    seedBoard('score', [lbEntry('Alpha', ['score' => 900])], 60);
    seedBoard('score', [lbEntry('Yankee', ['score' => 10])], 60, page: 2);

    board()
        ->assertSee('Alpha')->assertDontSee('Yankee')->assertSee('of 60')
        ->call('gotoPage', 2)
        ->assertSee('Yankee')->assertDontSee('Alpha')
        // 25 to a page: the first row of page two is player number 26
        ->assertSee('#26');
});

test('the podium styling belongs to the first page only', function () {
    seedBoard('score', [lbEntry('Yankee')], 60, page: 2);

    board()->call('gotoPage', 2)->assertDontSeeHtml('lb-row-1')->assertDontSeeHtml('lb-row-3');
});

test('reordering goes back to the first page', function () {
    seedBoard('score', [lbEntry('Alpha')], 60);
    seedBoard('score', [lbEntry('Yankee')], 60, page: 2);
    seedBoard('gold', [lbEntry('Gold')], 60);

    board()->call('gotoPage', 2)->call('sortBy', 'gold')->assertSet('paginators.page', 1)->assertSee('Gold');
});
