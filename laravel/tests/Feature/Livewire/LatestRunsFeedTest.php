<?php

use App\Livewire\LatestRunsFeed;
use App\Services\LatestRuns;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function feedRun(array $overrides = []): object
{
    return (object) array_merge([
        'time' => 65000, 'record_date' => '2026-01-01 10:00:00', 'start_speed' => 300,
        'user_uuid' => 'u1', 'user_name' => 'Alpha', 'auth_id' => 'STEAM_0:0:1', 'nationality' => 'ro',
        'map_uuid' => 'm1', 'map_name' => 'bhop_arcane', 'category_id' => 1, 'category_name' => 'Normal',
        'rank' => 1, 'best_time' => 65000,
        'age_seconds' => 7300, 'cached_at' => time(),
    ], $overrides);
}

/**
 * The component asks LatestRuns for a page, which is cache-backed: seeding the
 * page's key stands in for what the query would return.
 */
function seedFeed(array $rows, int $total, string $category = '', string $country = '', bool $records = false, int $page = 1): void
{
    Cache::put(LatestRuns::pageKey($page, LatestRuns::PER_PAGE, $category, $country, $records), ['rows' => collect($rows), 'total' => $total], 600);
}

beforeEach(function () {
    config(['services.steam.key' => null]);

    Cache::put('category_rules', collect([
        1 => (object) ['id' => 1, 'name' => 'Normal'],
        2 => (object) ['id' => 2, 'name' => 'Sideways'],
    ]), 600);
    Cache::put('latest_runs_countries', collect(['ro', 'de']), 600);

    $alpha = feedRun(['user_uuid' => 'u1', 'user_name' => 'Alpha', 'nationality' => 'ro', 'rank' => 1, 'category_id' => 1, 'category_name' => 'Normal']);
    $bravo = feedRun(['user_uuid' => 'u2', 'user_name' => 'Bravo', 'nationality' => 'de', 'rank' => 4, 'category_id' => 2, 'category_name' => 'Sideways', 'time' => 70000]);
    $charlie = feedRun(['user_uuid' => 'u3', 'user_name' => 'Charlie', 'nationality' => 'de', 'rank' => 2, 'category_id' => 1, 'category_name' => 'Normal', 'time' => 66000]);

    seedFeed([$alpha, $bravo, $charlie], 3);
    // What the query would return with each filter applied:
    seedFeed([$bravo], 1, category: '2');
    seedFeed([$alpha, $charlie], 2, category: '1');
    seedFeed([$bravo, $charlie], 2, country: 'de');
    seedFeed([$charlie], 1, category: '1', country: 'de');
    seedFeed([$alpha], 1, records: true);
});

function feed(): Testable
{
    return Livewire::test(LatestRunsFeed::class);
}

test('it lists a page of the newest runs', function () {
    feed()->assertSee('Alpha')->assertSee('Bravo')->assertSee('Charlie');
});

test('the category filter is handed to the query, not applied to a downloaded list', function () {
    feed()->set('category', '2')->assertSee('Bravo')->assertDontSee('Alpha');
});

test('the country filter is handed to the query', function () {
    feed()->set('country', 'de')->assertSee('Bravo')->assertSee('Charlie')->assertDontSee('Alpha');
});

test('records only is handed to the query', function () {
    feed()->set('recordsOnly', true)->assertSee('Alpha')->assertDontSee('Bravo');
});

test('filters combine', function () {
    feed()->set('country', 'de')->set('category', '1')->assertSee('Charlie')->assertDontSee('Bravo');
});

test('clearing resets every filter and returns to the first page', function () {
    seedFeed([], 0, category: '2', country: 'de');

    feed()
        ->set('country', 'de')->set('category', '2')
        ->call('clear')
        ->assertSet('country', '')->assertSet('category', '')->assertSet('recordsOnly', false)
        ->assertSee('Alpha');
});

test('a filter with no matches says so instead of showing an empty table', function () {
    seedFeed([], 0, category: '1', country: 'ro');

    feed()->set('category', '1')->set('country', 'ro')->assertSee('No runs match those filters.');
});

test('the filters offer the categories that exist and the countries that have players', function () {
    feed()
        ->assertSeeHtml('<option value="1">Normal</option>')->assertSeeHtml('<option value="2">Sideways</option>')
        ->assertSeeHtml('<option value="ro">RO</option>')->assertSeeHtml('<option value="de">DE</option>');
});

test('a long history pages, and each page is its own query', function () {
    seedFeed([feedRun(['user_name' => 'Alpha'])], 30);
    seedFeed([feedRun(['user_name' => 'Zulu', 'user_uuid' => 'u9'])], 30, page: 2);

    feed()
        ->assertSee('Alpha')->assertDontSee('Zulu')->assertSee('of 30')
        ->call('gotoPage', 2)
        ->assertSee('Zulu')->assertDontSee('Alpha');
});

test('changing a filter goes back to the first page', function () {
    seedFeed([feedRun()], 30);
    seedFeed([feedRun(['user_name' => 'Zulu'])], 30, page: 2);

    feed()
        ->call('gotoPage', 2)
        ->set('category', '2')
        ->assertSet('paginators.page', 1);
});

test('a record gets a Watch button that opens the replay, other runs do not', function () {
    feed()
        ->assertSeeHtml('/runs/m1/1/u1?tab=replay')
        ->assertDontSeeHtml('/runs/m1/2/u2?tab=replay')
        ->assertDontSeeHtml('/runs/m1/1/u3?tab=replay');
});

test('every run links to its own page', function () {
    feed()->assertSeeHtml('/runs/m1/2/u2')->assertSeeHtml('/runs/m1/1/u3');
});

test('the start speed is labelled in ups', function () {
    feed()->assertSee('Start (ups)')->assertSee('300');
});

test('when it was recorded shows as an age, with the exact time on hover', function () {
    feed()->assertSee('2h ago')->assertSeeHtml('title="2026-01-01 10:00:00"');
});

test('the age keeps counting while the feed sits in the cache', function () {
    seedFeed([feedRun(['age_seconds' => 60, 'cached_at' => time() - 3600])], 1);

    // 60s old when read, read an hour ago: an hour and a minute now.
    feed()->assertSee('1h ago');
});

test('the category is its own column', function () {
    feed()->assertSeeHtml('>Category</th>')->assertSee('Sideways');
});

test('a record gets a play button with no text, and it is labelled for screen readers', function () {
    $component = feed()->assertSeeHtml('aria-label="Watch the replay"');

    // The name lives in the attributes; nothing readable on the button itself.
    expect(strip_tags($component->html()))->not->toContain('Watch');
});

test('runs that are not records get no replay button', function () {
    // Only Alpha (rank 1) has a recording: one play button on the page, not three.
    expect(substr_count(feed()->html(), 'aria-label="Watch the replay"'))->toBe(1);
});
