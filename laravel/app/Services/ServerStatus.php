<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Live status of the game servers. This never touches a database: it is a UDP
 * query against each server, cached for a few seconds so that every page can
 * show the sidebar without hammering them.
 */
class ServerStatus
{
    /**
     * @var array<int, array{name: string, host: string, port: int, aliases: array<int, string>}>
     */
    private const SERVERS = [
        ['name' => 'Bhop', 'host' => 'bhop.laleagane.ro', 'port' => 27015, 'aliases' => []],
        ['name' => 'Deathrun', 'host' => 'dr.laleagane.ro', 'port' => 27015, 'aliases' => ['dr.llg.ro']],
        ['name' => 'Speedrun', 'host' => 'bhop.laleagane.ro', 'port' => 27016, 'aliases' => []],
        ['name' => 'Bhop Brazil', 'host' => '190.115.197.245', 'port' => 27015, 'aliases' => []],
    ];

    public function __construct(private readonly GameServerQuery $query) {}

    public function all(): Collection
    {
        return Cache::remember(
            'home_live_servers',
            now()->addSeconds(45),
            fn () => collect(self::SERVERS)->map(fn ($server) => $this->probe($server))
        );
    }

    /**
     * @param  array{name: string, host: string, port: int, aliases: array<int, string>}  $server
     */
    private function probe(array $server): array
    {
        $info = null;
        $resolvedHost = $server['host'];

        foreach (array_merge([$server['host']], $server['aliases']) as $host) {
            $info = $this->query->info($host, $server['port']);

            if ($info !== null) {
                $resolvedHost = $host;
                break;
            }
        }

        return array_merge($server, [
            'query_host' => $resolvedHost,
            'online' => $info !== null,
            'map' => $info['map'] ?? null,
            'players' => $info['players'] ?? null,
            'max_players' => $info['max_players'] ?? null,
            'server_name' => $info['name'] ?? null,
        ]);
    }
}
