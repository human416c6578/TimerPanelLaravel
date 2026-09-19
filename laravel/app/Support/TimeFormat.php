<?php

namespace App\Support;

class TimeFormat
{
    /**
     * A run time. The game database stores these as milliseconds.
     */
    public static function runtime(int|float|string|null $milliseconds): string
    {
        if ($milliseconds === null || $milliseconds === '') {
            return '—';
        }

        $milliseconds = max(0, (int) $milliseconds);

        $hours = intdiv($milliseconds, 3600000);
        $minutes = intdiv($milliseconds % 3600000, 60000);
        $seconds = intdiv($milliseconds % 60000, 1000);
        $millis = $milliseconds % 1000;

        $tail = sprintf('%02d:%02d.%03d', $minutes, $seconds, $millis);

        return $hours > 0 ? "{$hours}:{$tail}" : $tail;
    }

    /**
     * Time spent on the server, stored as seconds.
     */
    public static function played(int|float|string|null $seconds): string
    {
        $seconds = max(0, (int) $seconds);

        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60
        );
    }

    /**
     * The gap to a record, in milliseconds: +0.421 or +1:02.310. Zero means
     * the run *is* the record and reads as such.
     */
    public static function delta(int|float|string|null $milliseconds): string
    {
        if ($milliseconds === null || $milliseconds === '') {
            return '—';
        }

        $milliseconds = (int) $milliseconds;

        if ($milliseconds <= 0) {
            return 'WR';
        }

        if ($milliseconds < 60000) {
            return sprintf('+%d.%03d', intdiv($milliseconds, 1000), $milliseconds % 1000);
        }

        return '+'.self::runtime($milliseconds);
    }
}
