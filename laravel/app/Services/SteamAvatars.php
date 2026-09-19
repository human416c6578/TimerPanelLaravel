<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Steam profile pictures for the players on a page.
 *
 * The game database stores only the SteamID ("STEAM_0:1:11111"); the picture
 * lives on Steam. One request resolves up to 100 players, every answer is
 * cached, and a Steam that is slow or down costs one short wait and then a
 * minute of silence, never a delay on every page.
 */
class SteamAvatars
{
    private const ENDPOINT = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/';

    private const FOUND_TTL = 21600;   // 6 hours

    private const MISSING_TTL = 600;   // private or unknown profiles: try again in 10 minutes

    private const BACKOFF_TTL = 60;    // Steam unreachable: leave it alone for a minute

    private const BATCH = 100;

    /** STEAM_0:1:11111 -> 76561197960287223, or null when it is not a SteamID. */
    public function steamId64(?string $authId): ?string
    {
        if ($authId !== null && preg_match('/^STEAM_[0-5]:([01]):(\d+)$/', $authId, $matches)) {
            return bcadd(bcadd(bcmul($matches[2], '2'), $matches[1]), '76561197960265728');
        }

        return null;
    }

    public function configured(): bool
    {
        return filled(config('services.steam.key'));
    }

    /**
     * Small (32px) avatars for a set of players, in one round trip at most.
     *
     * @param  iterable<string>  $authIds
     * @return array<string, string> auth_id => image url; players without one are simply absent
     */
    public function small(iterable $authIds): array
    {
        $found = [];

        foreach ($this->resolve($authIds) as $authId => $profile) {
            if ($profile !== null) {
                $found[$authId] = $profile['avatar'];
            }
        }

        return $found;
    }

    /**
     * The full profile summary of one player, or null.
     *
     * @return array{steamid64: string, avatar: string, medium: string, full: string}|null
     */
    public function profile(?string $authId): ?array
    {
        return $authId === null ? null : ($this->resolve([$authId])[$authId] ?? null);
    }

    /**
     * @param  iterable<string>  $authIds
     * @return array<string, array{steamid64: string, avatar: string, medium: string, full: string}|null>
     */
    private function resolve(iterable $authIds): array
    {
        if (! $this->configured()) {
            return [];
        }

        $ids = [];

        foreach ($authIds as $authId) {
            if (($id64 = $this->steamId64($authId)) !== null) {
                $ids[$authId] = $id64;
            }
        }

        if ($ids === []) {
            return [];
        }

        // What the cache already knows: an array is a profile, false is a known miss.
        // array_values matters: many() reads string keys as "key => default", and
        // $ids is keyed by SteamID, so passing it through would look up the wrong keys.
        $cached = Cache::many(array_map(fn ($id64) => $this->key($id64), array_values(array_unique($ids))));

        $result = [];
        $unknown = [];

        foreach ($ids as $authId => $id64) {
            $hit = $cached[$this->key($id64)] ?? null;

            if (is_array($hit)) {
                $result[$authId] = $hit;
            } elseif ($hit === false) {
                $result[$authId] = null;
            } else {
                $unknown[$id64] = $authId;
            }
        }

        if ($unknown === [] || Cache::has('steam_api_backoff')) {
            return $result;
        }

        foreach (array_chunk(array_keys($unknown), self::BATCH) as $chunk) {
            $players = $this->fetch($chunk);

            if ($players === null) {
                Cache::put('steam_api_backoff', true, self::BACKOFF_TTL);

                break;
            }

            foreach ($chunk as $id64) {
                $authId = $unknown[$id64];
                $profile = $players[$id64] ?? null;

                $result[$authId] = $profile;
                Cache::put($this->key($id64), $profile ?? false, $profile ? self::FOUND_TTL : self::MISSING_TTL);
            }
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $id64s
     * @return array<string, array{steamid64: string, avatar: string, medium: string, full: string}>|null null when Steam could not be reached
     */
    private function fetch(array $id64s): ?array
    {
        try {
            $response = Http::timeout(3)->get(self::ENDPOINT, [
                'key' => config('services.steam.key'),
                'steamids' => implode(',', $id64s),
            ]);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $players = [];

        foreach ((array) $response->json('response.players', []) as $player) {
            if (! isset($player['steamid'], $player['avatar'])) {
                continue;
            }

            $players[(string) $player['steamid']] = [
                'steamid64' => (string) $player['steamid'],
                'avatar' => $player['avatar'],
                'medium' => $player['avatarmedium'] ?? $player['avatar'],
                'full' => $player['avatarfull'] ?? $player['avatar'],
            ];
        }

        return $players;
    }

    private function key(string $id64): string
    {
        return "steam_avatar_{$id64}";
    }
}
