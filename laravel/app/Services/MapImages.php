<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Local copies of map pictures. A picture is fetched from the configured
 * sources once, proven to be an image, and kept on this server's disk; after
 * that it is served from here and no third party is contacted again.
 */
class MapImages
{
    private const DIRECTORY = 'map-images';

    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    /** Only names a map could actually have: no slashes, no dots at the start. */
    public function validName(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9][A-Za-z0-9_.\-]{0,63}$/', $name);
    }

    public function enabled(): bool
    {
        return config('maps.image_sources') !== [];
    }

    /** The stored picture's path on the disk, or null. */
    public function find(string $name): ?string
    {
        if (! $this->validName($name)) {
            return null;
        }

        return $this->index()[strtolower($name)] ?? null;
    }

    /**
     * Every stored picture, by lowercase map name. Read once per request, so a page
     * of thirty covers costs one directory listing rather than thirty lookups.
     *
     * @return array<string, string>
     */
    private function index(): array
    {
        if ($this->index === null) {
            $this->index = [];

            foreach ($this->disk()->files(self::DIRECTORY) as $path) {
                $extension = pathinfo($path, PATHINFO_EXTENSION);

                if (in_array($extension, self::TYPES, true)) {
                    $this->index[strtolower(pathinfo($path, PATHINFO_FILENAME))] = $path;
                }
            }
        }

        return $this->index;
    }

    /** @var array<string, string>|null */
    private ?array $index = null;

    /** How many pictures are stored. */
    public function count(): int
    {
        return count($this->index());
    }

    /**
     * Keep a picture for a map, replacing any it had. The bytes must be a real
     * image of an accepted type and size, whatever they were labelled.
     *
     * @return string|null the stored path, or null when the picture is not acceptable
     */
    public function save(string $name, string $bytes): ?string
    {
        if (! $this->validName($name) || strlen($bytes) > config('maps.image_max_bytes')) {
            return null;
        }

        $info = @getimagesizefromstring($bytes);
        $extension = is_array($info) ? (self::TYPES[$info['mime']] ?? null) : null;

        if ($extension === null) {
            return null;
        }

        $this->forget($name);

        $path = self::DIRECTORY."/{$name}.{$extension}";
        $this->disk()->put($path, $bytes);
        $this->index = null;

        return $path;
    }

    /** Remove a map's picture, and forget that it was ever missing. */
    public function forget(string $name): void
    {
        if (! $this->validName($name)) {
            return;
        }

        foreach (self::TYPES as $extension) {
            $this->disk()->delete(self::DIRECTORY."/{$name}.{$extension}");
        }

        Cache::forget($this->missKey($name));
        $this->index = null;
    }

    /** True while a recent search for this map came up empty. */
    public function knownMissing(string $name): bool
    {
        return Cache::has($this->missKey($name));
    }

    /**
     * Try each source in turn and keep the first real image. Remembers a
     * failure, so an unknown map does not send a request on every page view.
     */
    public function fetch(string $name): ?string
    {
        if (! $this->enabled() || ! $this->validName($name)) {
            return null;
        }

        foreach (config('maps.image_sources') as $template) {
            $url = str_replace('{map}', rawurlencode($name), $template);

            try {
                $response = Http::timeout(4)->withUserAgent('TimerPanel/1.0 (map picture cache)')->accept('image/*')->get($url);
            } catch (Throwable) {
                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            $type = strtolower(trim(explode(';', $response->header('Content-Type'))[0]));
            $body = $response->body();

            // A "no picture" image served with a 200 is a miss, not a picture.
            if (in_array(md5($body), config('maps.image_reject_hashes'), true)) {
                continue;
            }

            // The header says image; save() insists the bytes agree.
            if (isset(self::TYPES[$type]) && ($path = $this->save($name, $body)) !== null) {
                return $path;
            }
        }

        Cache::put($this->missKey($name), true, now()->addHours(config('maps.image_miss_hours')));

        return null;
    }

    public function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk('local');
    }

    private function missKey(string $name): string
    {
        return 'map_image_missing_'.strtolower($name);
    }
}
