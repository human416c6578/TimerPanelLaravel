<?php

use App\Services\SteamAvatars;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.steam.key' => 'test-key']);
    Cache::flush();
});

function steamResponse(array $players): array
{
    return ['api.steampowered.com/*' => Http::response(['response' => ['players' => $players]])];
}

function steamPlayer(string $id64, string $avatar = 'https://cdn.example/a.jpg'): array
{
    return ['steamid' => $id64, 'avatar' => $avatar, 'avatarmedium' => $avatar, 'avatarfull' => $avatar];
}

test('a SteamID becomes the 64-bit id Steam\'s API wants', function () {
    $steam = new SteamAvatars;

    expect($steam->steamId64('STEAM_0:1:11111'))->toBe('76561197960287951')
        ->and($steam->steamId64('STEAM_0:0:22222'))->toBe('76561197960310172')
        ->and($steam->steamId64('not a steam id'))->toBeNull()
        ->and($steam->steamId64(null))->toBeNull();
});

test('a page of players costs one request, not one each', function () {
    Http::fake(steamResponse([steamPlayer('76561197960287951'), steamPlayer('76561197960310172')]));

    $avatars = app(SteamAvatars::class)->small(['STEAM_0:1:11111', 'STEAM_0:0:22222']);

    expect($avatars)->toHaveKeys(['STEAM_0:1:11111', 'STEAM_0:0:22222']);
    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => str_contains($request['steamids'], '76561197960287951,76561197960310172'));
});

test('answers are cached, so the second look asks nobody', function () {
    Http::fake(steamResponse([steamPlayer('76561197960287951')]));

    app(SteamAvatars::class)->small(['STEAM_0:1:11111']);
    app(SteamAvatars::class)->small(['STEAM_0:1:11111']);

    Http::assertSentCount(1);
});

test('a private or unknown profile is remembered as missing rather than asked about again', function () {
    Http::fake(steamResponse([]));

    expect(app(SteamAvatars::class)->small(['STEAM_0:1:11111']))->toBe([]);

    app(SteamAvatars::class)->small(['STEAM_0:1:11111']);

    Http::assertSentCount(1);
});

test('only the players not yet cached are requested', function () {
    Http::fake(steamResponse([steamPlayer('76561197960287951')]));
    app(SteamAvatars::class)->small(['STEAM_0:1:11111']);

    Http::fake(steamResponse([steamPlayer('76561197960310172')]));
    app(SteamAvatars::class)->small(['STEAM_0:1:11111', 'STEAM_0:0:22222']);

    Http::assertSent(fn (Request $request) => $request['steamids'] === '76561197960310172');
});

test('a Steam that is down costs one attempt, then a minute of silence', function () {
    Http::fake(['api.steampowered.com/*' => Http::response('', 503)]);

    expect(app(SteamAvatars::class)->small(['STEAM_0:1:11111']))->toBe([]);
    expect(app(SteamAvatars::class)->small(['STEAM_0:0:22222']))->toBe([]);

    Http::assertSentCount(1);
});

test('nothing is requested without an API key', function () {
    config(['services.steam.key' => null]);
    Http::fake();

    expect(app(SteamAvatars::class)->small(['STEAM_0:1:11111']))->toBe([]);

    Http::assertNothingSent();
});

test('ids that are not SteamIDs are skipped without a request', function () {
    Http::fake();

    expect(app(SteamAvatars::class)->small(['bot', '']))->toBe([]);

    Http::assertNothingSent();
});

test('the profile page gets the full-size picture', function () {
    Http::fake(steamResponse([['steamid' => '76561197960287951', 'avatar' => 's.jpg', 'avatarmedium' => 'm.jpg', 'avatarfull' => 'f.jpg']]));

    expect(app(SteamAvatars::class)->profile('STEAM_0:1:11111'))->toMatchArray(['full' => 'f.jpg', 'avatar' => 's.jpg']);
});
