<?php

use App\Services\PlayerProfile;

function playerRun(int $rank, ?int $delta, string $map = 'bhop_a'): object
{
    return (object) ['Rank' => $rank, 'Delta' => $delta, 'MapName' => $map];
}

test('rank distribution counts podiums and top tens', function () {
    $summary = (new PlayerProfile)->rankSummary(collect([
        playerRun(1, 0), playerRun(1, 0), playerRun(2, 400), playerRun(3, 900),
        playerRun(7, 3000), playerRun(25, 20000),
    ]));

    expect($summary)->toMatchArray([
        'first' => 2,
        'second' => 1,
        'third' => 1,
        'top10' => 5,
        'total' => 6,
        'best' => 1,
    ]);
});

test('an empty history has no best rank rather than zero', function () {
    $summary = (new PlayerProfile)->rankSummary(collect());

    expect($summary['total'])->toBe(0)
        ->and($summary['best'])->toBeNull();
});

test('records within reach exclude the ones already held and sort by gap', function () {
    $reach = (new PlayerProfile)->withinReach(collect([
        playerRun(1, 0, 'held'),
        playerRun(4, 9000, 'far'),
        playerRun(2, 400, 'close'),
        playerRun(3, 1500, 'near'),
        playerRun(2, null, 'unknown gap'),
    ]));

    expect($reach->pluck('MapName')->all())->toBe(['close', 'near', 'far']);
});

test('records within reach respects the limit', function () {
    $runs = collect(range(2, 12))->map(fn ($rank) => playerRun($rank, $rank * 100));

    expect((new PlayerProfile)->withinReach($runs, 3))->toHaveCount(3);
});

function insightRun(int $rank, int $runs, string $category = 'Normal', ?float $sync = null, string $date = '2026-01-01'): object
{
    return (object) [
        'Rank' => $rank, 'Runs' => $runs, 'CategoryName' => $category, 'CategoryId' => 1,
        'Sync' => $sync, 'RecordDate' => $date, 'MapName' => 'm', 'MapUUID' => 'm1',
        'Time' => 60000, 'Delta' => 0,
    ];
}

test('a rank is named by how good it is', function (?int $rank, string $tier) {
    expect((new PlayerProfile)->tier($rank))->toBe($tier);
})->with([
    'record' => [1, 'wr'], 'second' => [2, 'podium'], 'third' => [3, 'podium'],
    'fourth' => [4, 'top10'], 'tenth' => [10, 'top10'], 'eleventh' => [11, 'other'],
    'unranked' => [null, 'other'],
]);

test('insights count records and shares from the loaded runs', function () {
    $insights = (new PlayerProfile)->insights(collect([
        insightRun(1, 20), insightRun(1, 20), insightRun(4, 20), insightRun(30, 40),
    ]));

    expect($insights['total'])->toBe(4)
        ->and($insights['records'])->toBe(2)
        ->and($insights['top10'])->toBe(3)
        ->and($insights['recordShare'])->toBe(0.5);
});

test('average sync ignores runs the timer recorded no sync for', function () {
    $insights = (new PlayerProfile)->insights(collect([
        insightRun(1, 20, sync: 90.0), insightRun(2, 20, sync: 80.0), insightRun(3, 20, sync: null),
    ]));

    expect($insights['avgSync'])->toBe(85.0);
});

test('there is no average sync when no run has one', function () {
    expect((new PlayerProfile)->insights(collect([insightRun(1, 20)]))['avgSync'])->toBeNull();
});

test('typical position only counts fields of five or more', function () {
    // Two tiny fields would read as "top 50%" and "top 100%" and mean nothing.
    $tiny = (new PlayerProfile)->insights(collect([insightRun(1, 2), insightRun(2, 2)]));

    expect($tiny['typicalPosition'])->toBeNull();

    $real = (new PlayerProfile)->insights(collect([insightRun(2, 20), insightRun(10, 20)]));

    expect($real['typicalPosition'])->toEqualWithDelta(0.3, 0.0001);
});

test('categories are ordered by how much they are run, with their records', function () {
    $insights = (new PlayerProfile)->insights(collect([
        insightRun(1, 9, 'Sideways'), insightRun(5, 9, 'Normal'), insightRun(1, 9, 'Normal'), insightRun(3, 9, 'Normal'),
    ]));

    expect($insights['categories']->pluck('name')->all())->toBe(['Normal', 'Sideways'])
        ->and($insights['categories']->first()->runs)->toBe(3)
        ->and($insights['categories']->first()->records)->toBe(1);
});

test('the form strip is the last ten runs, newest first', function () {
    $runs = collect(range(1, 15))->map(fn ($n) => insightRun($n, 30, date: sprintf('2026-01-%02d', $n)));

    $form = (new PlayerProfile)->insights($runs)['form'];

    expect($form)->toHaveCount(10)
        ->and($form->first()->date)->toBe('2026-01-15')
        ->and($form->last()->date)->toBe('2026-01-06');
});

function versusRun(string $map, int $category, int $time, ?float $sync = null): object
{
    return (object) [
        'MapUUID' => $map, 'MapName' => $map, 'CategoryId' => $category, 'CategoryName' => 'C'.$category,
        'Time' => $time, 'Rank' => 1, 'Sync' => $sync,
    ];
}

test('versus only looks at maps and categories both players have run', function () {
    $versus = (new PlayerProfile)->versus(
        collect([versusRun('a', 1, 60000), versusRun('b', 1, 70000), versusRun('only-left', 1, 1)]),
        collect([versusRun('a', 1, 65000), versusRun('b', 1, 68000), versusRun('a', 2, 1)]),
    );

    expect($versus['common'])->toBe(2);
});

test('versus scores who was faster on each shared run', function () {
    $versus = (new PlayerProfile)->versus(
        collect([versusRun('a', 1, 60000), versusRun('b', 1, 70000), versusRun('c', 1, 50000)]),
        collect([versusRun('a', 1, 65000), versusRun('b', 1, 68000), versusRun('c', 1, 50000)]),
    );

    expect($versus['aWins'])->toBe(1)
        ->and($versus['bWins'])->toBe(1)
        ->and($versus['ties'])->toBe(1)
        // |60-65| = 5s, |70-68| = 2s, 0s => 7s / 3 runs
        ->and($versus['avgGap'])->toBe(2333);
});

test('a versus with nothing in common is empty rather than an error', function () {
    $versus = (new PlayerProfile)->versus(collect([versusRun('a', 1, 1)]), collect([versusRun('b', 1, 1)]));

    expect($versus['common'])->toBe(0)
        ->and($versus['avgGap'])->toBeNull()
        ->and($versus['aSync'])->toBeNull();
});

test('versus averages sync only over runs where both players have it', function () {
    $versus = (new PlayerProfile)->versus(
        collect([versusRun('a', 1, 1, 90.0), versusRun('b', 1, 1, 80.0)]),
        collect([versusRun('a', 1, 1, 70.0), versusRun('b', 1, 1, null)]),
    );

    expect($versus['aSync'])->toBe(90.0)
        ->and($versus['bSync'])->toBe(70.0);
});
