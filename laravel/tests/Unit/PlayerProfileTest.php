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
