<?php

use App\Services\MapImages;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportDisablingBackButtonCache\SupportDisablingBackButtonCache;

// A real 1x1 PNG: the service checks the bytes, not just the header.
const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

beforeEach(function () {
    // Livewire sets a static "no back-button cache" flag whenever a component boots.
    // In production that is one request; here it would leak in from earlier tests
    // in the same process and rewrite the picture's Cache-Control.
    SupportDisablingBackButtonCache::$disableBackButtonCache = false;

    Storage::fake('local');
    Cache::flush();
    config(['maps.image_sources' => ['https://pics.example/maps/{map}.png']]);
});

test('a picture is fetched once and stored on this server', function () {
    Http::fake(['pics.example/*' => Http::response(base64_decode(PNG), 200, ['Content-Type' => 'image/png'])]);

    $images = app(MapImages::class);

    expect($images->fetch('bhop_arcane'))->toBe('map-images/bhop_arcane.png');
    Storage::disk('local')->assertExists('map-images/bhop_arcane.png');
    expect($images->find('bhop_arcane'))->toBe('map-images/bhop_arcane.png');
});

test('nothing is fetched when no source is configured', function () {
    config(['maps.image_sources' => []]);
    Http::fake();

    expect(app(MapImages::class)->fetch('bhop_arcane'))->toBeNull();

    Http::assertNothingSent();
});

test('a response that only claims to be an image is refused', function () {
    Http::fake(['pics.example/*' => Http::response('<html>not an image</html>', 200, ['Content-Type' => 'image/png'])]);

    expect(app(MapImages::class)->fetch('bhop_arcane'))->toBeNull();
    Storage::disk('local')->assertMissing('map-images/bhop_arcane.png');
});

test('a page that is not an image type is refused', function () {
    Http::fake(['pics.example/*' => Http::response(base64_decode(PNG), 200, ['Content-Type' => 'text/html'])]);

    expect(app(MapImages::class)->fetch('bhop_arcane'))->toBeNull();
});

test('an oversized picture is refused', function () {
    config(['maps.image_max_bytes' => 10]);
    Http::fake(['pics.example/*' => Http::response(base64_decode(PNG), 200, ['Content-Type' => 'image/png'])]);

    expect(app(MapImages::class)->fetch('bhop_arcane'))->toBeNull();
});

test('a missing picture is remembered so the source is not asked on every page', function () {
    Http::fake(['pics.example/*' => Http::response('', 404)]);

    $images = app(MapImages::class);
    $images->fetch('bhop_arcane');

    expect($images->knownMissing('bhop_arcane'))->toBeTrue();
});

test('the next source is tried when the first has nothing', function () {
    config(['maps.image_sources' => ['https://first.example/{map}.png', 'https://second.example/{map}.png']]);
    Http::fake([
        'first.example/*' => Http::response('', 404),
        'second.example/*' => Http::response(base64_decode(PNG), 200, ['Content-Type' => 'image/png']),
    ]);

    expect(app(MapImages::class)->fetch('bhop_arcane'))->toBe('map-images/bhop_arcane.png');
});

test('names that could reach outside the pictures folder are not maps', function (string $name) {
    expect(app(MapImages::class)->validName($name))->toBeFalse();
})->with(['../../.env', 'a/b', '..', '.hidden', 'x y', '', str_repeat('a', 65)]);

test('ordinary map names are fine', function (string $name) {
    expect(app(MapImages::class)->validName($name))->toBeTrue();
})->with(['bhop_arcane', 'de_dust2', 'bhop-lego2', 'kz_map.v2', 'Bhop_ABC']);

test('a stored picture is served straight from disk with a cache header', function () {
    Storage::disk('local')->put('map-images/bhop_arcane.png', base64_decode(PNG));

    $this->get('/map-images/bhop_arcane')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Cache-Control', 'max-age=604800, public');
});

test('the picture route refuses a path-shaped name', function () {
    $this->get('/map-images/'.rawurlencode('../.env'))->assertNotFound();
});

test('a map with no stored picture and a recent miss is a plain 404, without a lookup', function () {
    Http::fake();
    Cache::put('map_image_missing_bhop_arcane', true, 600);

    $this->get('/map-images/bhop_arcane')->assertNotFound();

    Http::assertNothingSent();
});

test('a "no picture" placeholder served with a 200 is a miss, not the map\'s picture', function () {
    $placeholder = base64_decode(PNG);
    config(['maps.image_reject_hashes' => [md5($placeholder)]]);
    Http::fake(['pics.example/*' => Http::response($placeholder, 200, ['Content-Type' => 'image/png'])]);

    $images = app(MapImages::class);

    expect($images->fetch('bhop_arcane'))->toBeNull()
        ->and($images->knownMissing('bhop_arcane'))->toBeTrue();
    Storage::disk('local')->assertMissing('map-images/bhop_arcane.png');
});

test('the next source is tried after a placeholder', function () {
    $placeholder = base64_decode(PNG);
    // A different, real image for the second source.
    $real = base64_decode('R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs=');

    config([
        'maps.image_reject_hashes' => [md5($placeholder)],
        'maps.image_sources' => ['https://first.example/{map}.png', 'https://second.example/{map}.gif'],
    ]);
    Http::fake([
        'first.example/*' => Http::response($placeholder, 200, ['Content-Type' => 'image/png']),
        'second.example/*' => Http::response($real, 200, ['Content-Type' => 'image/gif']),
    ]);

    expect(app(MapImages::class)->fetch('bhop_arcane'))->toBe('map-images/bhop_arcane.gif');
});

test('the bundled placeholder hash is banners.gametracker.rs\'s "no picture" image', function () {
    expect(config('maps.image_reject_hashes'))->toContain('f196e1c520b0c8d2b054f7c97bf0e80f');
});

test('sources identify themselves with a user agent', function () {
    Http::fake(['pics.example/*' => Http::response('', 404)]);

    app(MapImages::class)->fetch('bhop_arcane');

    Http::assertSent(fn ($request) => str_contains($request->header('User-Agent')[0] ?? '', 'TimerPanel'));
});
