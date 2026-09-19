<?php

namespace App\Http\Controllers;

use App\Models\Map;
use App\Services\MapImages;
use Symfony\Component\HttpFoundation\Response;

class MapImageController extends Controller
{
    /**
     * A map's picture, from the local copy. The first request for a map that
     * has none yet fetches it from the configured sources; every later one is
     * a file read.
     */
    public function show(string $name, MapImages $images): Response
    {
        abort_unless($images->validName($name), 404);

        $path = $images->find($name);

        if ($path === null && ! $images->knownMissing($name)) {
            // Only maps that exist may trigger an outbound request, so the
            // route cannot be used to make this server fetch arbitrary names.
            abort_unless(Map::where('name', $name)->exists(), 404);

            $path = $images->fetch($name);
        }

        abort_if($path === null, 404);

        return $images->disk()->response($path, null, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
