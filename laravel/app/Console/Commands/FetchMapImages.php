<?php

namespace App\Console\Commands;

use App\Models\Map;
use App\Services\MapImages;
use Illuminate\Console\Command;

class FetchMapImages extends Command
{
    protected $signature = 'maps:fetch-images {--force : Ask again even for maps already known to have no picture}';

    protected $description = 'Download the picture of every map from the configured MAP_IMAGE_SOURCES';

    public function handle(MapImages $images): int
    {
        if (! $images->enabled()) {
            $this->components->warn('MAP_IMAGE_SOURCES is empty, so there is nowhere to fetch pictures from.');

            return self::FAILURE;
        }

        $found = $missing = $kept = 0;

        foreach (Map::orderBy('name')->pluck('name') as $name) {
            if ($images->find($name) !== null) {
                $kept++;

                continue;
            }

            if (! $this->option('force') && $images->knownMissing($name)) {
                $missing++;

                continue;
            }

            // One source after another, one map at a time, with a pause: this is somebody
            // else's server, and there is no hurry.
            usleep(250_000);

            if ($images->fetch($name) !== null) {
                $found++;
                $this->line("  <info>+</info> {$name}");
            } else {
                $missing++;
            }
        }

        $this->components->info("{$found} fetched, {$kept} already stored, {$missing} without a picture.");

        return self::SUCCESS;
    }
}
