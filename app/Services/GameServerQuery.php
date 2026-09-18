<?php

namespace App\Services;

class GameServerQuery
{
    public function info(
        string $host,
        int $port = 27015,
        float $timeout = 1.0,
    ): ?array {
        $packet = "\xFF\xFF\xFF\xFFTSource Engine Query\x00";
        $response = $this->send($host, $port, $packet, $timeout);

        if ($response === null || strlen($response) < 5) {
            return null;
        }

        $type = ord($response[4]);

        if ($type === 0x41 && strlen($response) >= 9) {
            $challenge = substr($response, 5, 4);
            $response = $this->send(
                $host,
                $port,
                $packet . $challenge,
                $timeout,
            );

            if ($response === null || strlen($response) < 5) {
                return null;
            }

            $type = ord($response[4]);
        }

        return match ($type) {
            0x49 => $this->parseSourceInfo($response),
            0x6d => $this->parseGoldSrcInfo($response),
            default => null,
        };
    }

    private function send(
        string $host,
        int $port,
        string $packet,
        float $timeout,
    ): ?string {
        $errno = 0;
        $errstr = "";
        $seconds = (int) floor($timeout);
        $microseconds = (int) (($timeout - $seconds) * 1000000);

        $socket = @fsockopen("udp://{$host}", $port, $errno, $errstr, $timeout);

        if ($socket === false) {
            return null;
        }

        stream_set_timeout($socket, $seconds, $microseconds);

        $bytesWritten = @fwrite($socket, $packet);

        if ($bytesWritten === false || $bytesWritten === 0) {
            fclose($socket);

            return null;
        }

        $response = @fread($socket, 4096);
        fclose($socket);

        return $response !== false && $response !== "" ? $response : null;
    }

    private function parseSourceInfo(string $response): ?array
    {
        $offset = 6;
        $name = $this->readString($response, $offset);
        $map = $this->readString($response, $offset);

        $this->readString($response, $offset);
        $this->readString($response, $offset);

        if (!isset($response[$offset + 4])) {
            return null;
        }

        $offset += 2;
        $players = ord($response[$offset]);
        $maxPlayers = ord($response[$offset + 1]);

        return [
            "name" => $name,
            "map" => $map,
            "players" => $players,
            "max_players" => $maxPlayers,
        ];
    }

    private function parseGoldSrcInfo(string $response): ?array
    {
        $offset = 5;
        $this->readString($response, $offset);
        $name = $this->readString($response, $offset);
        $map = $this->readString($response, $offset);

        $this->readString($response, $offset);
        $this->readString($response, $offset);

        if (!isset($response[$offset + 2])) {
            return null;
        }

        $players = ord($response[$offset]);
        $maxPlayers = ord($response[$offset + 1]);

        return [
            "name" => $name,
            "map" => $map,
            "players" => $players,
            "max_players" => $maxPlayers,
        ];
    }

    private function readString(string $data, int &$offset): string
    {
        $end = strpos($data, "\x00", $offset);

        if ($end === false) {
            $end = strlen($data);
        }

        $value = substr($data, $offset, $end - $offset);
        $offset = $end + 1;

        return $value;
    }
}
