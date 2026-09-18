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
}
