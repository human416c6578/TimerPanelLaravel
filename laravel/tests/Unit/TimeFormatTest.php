<?php

use App\Support\TimeFormat;

test('run times are minutes, seconds and milliseconds', function (int $ms, string $expected) {
    expect(TimeFormat::runtime($ms))->toBe($expected);
})->with([
    'sub-minute' => [45200, '00:45.200'],
    'over a minute' => [64231, '01:04.231'],
    'exactly a minute' => [60000, '01:00.000'],
    'over an hour' => [3723456, '1:02:03.456'],
    'zero' => [0, '00:00.000'],
]);

test('a missing run time is a dash, not zero', function () {
    expect(TimeFormat::runtime(null))->toBe('—')
        ->and(TimeFormat::runtime(''))->toBe('—');
});

test('played time is hours, minutes and seconds', function () {
    expect(TimeFormat::played(54321))->toBe('15:05:21')
        ->and(TimeFormat::played(59))->toBe('00:00:59')
        ->and(TimeFormat::played(null))->toBe('00:00:00');
});

test('the gap to a record', function (int|string|null $ms, string $expected) {
    expect(TimeFormat::delta($ms))->toBe($expected);
})->with([
    'the record itself' => [0, 'WR'],
    'a negative gap reads as the record' => [-5, 'WR'],
    'under a second' => [421, '+0.421'],
    'seconds' => [7749, '+7.749'],
    'over a minute' => [62310, '+01:02.310'],
    'unknown' => [null, '—'],
]);

test('how long ago is as short as it can be', function (int $seconds, string $expected) {
    expect(TimeFormat::age($seconds))->toBe($expected);
})->with([
    'moments' => [12, 'just now'],
    'minutes' => [12 * 60 + 5, '12m ago'],
    'an hour' => [3600, '1h ago'],
    'hours' => [3 * 3600 + 1800, '3h ago'],
    'a day' => [86400, '1d ago'],
    'days' => [5 * 86400, '5d ago'],
    'never negative' => [-30, 'just now'],
]);

test('an unknown age is a dash', function () {
    expect(TimeFormat::age(null))->toBe('—');
});
