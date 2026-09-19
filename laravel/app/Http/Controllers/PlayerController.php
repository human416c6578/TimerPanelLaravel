<?php

namespace App\Http\Controllers;

use App\Models\GameUser;
use App\Models\PlayedTime;
use App\Models\PlayedTimeInfo;
use App\Models\Time;
use App\Services\PlayerProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PlayerController extends Controller
{
    public function index(Request $request)
    {
        return view('player.list', ['search' => trim((string) $request->input('search', ''))]);
    }

    public function profile(Request $request, $uuid, PlayerProfile $profile)
    {
        $user = GameUser::select('uuid', 'name', 'auth_id', 'nationality')->where('uuid', $uuid)->firstOrFail();

        $authId = $user->auth_id;

        $steamId64 = $this->convertToSteamID64($authId);

        $steamAvatar = null;
        if ($steamId64) {
            $steamAvatar = Cache::remember(
                "steam_profile_{$steamId64}",
                3600,
                function () use ($steamId64) {
                    $apiKey = env('STEAM_API_KEY');
                    $response = Http::get(
                        'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/',
                        [
                            'key' => $apiKey,
                            'steamids' => $steamId64,
                        ]
                    );

                    return $response->json('response.players.0');
                }
            );
        }

        $steamData = [
            'steamid64' => $steamId64,
            'avatar' => $steamAvatar['avatarfull'] ?? null,
        ];

        // Total time played (sum all time_played across servers)
        $totalTimePlayed = PlayedTime::where('auth_id', $user->auth_id)->sum(
            'time_played'
        );

        $totalTimes = Time::where('user_uuid', $uuid)->count();

        $chartData = $this->generatePlayedTimeChartData($user->auth_id);

        // The same cached collection the records table pages through.
        $allRuns = $profile->runs($uuid);

        $rankSummary = $profile->rankSummary($allRuns);
        $withinReach = $profile->withinReach($allRuns);
        $medals = $profile->medals($uuid);
        $insights = $profile->insights($allRuns);

        return view(
            'player.profile',
            compact(
                'user',
                'steamData',
                'totalTimePlayed',
                'totalTimes',
                'chartData',
                'rankSummary',
                'withinReach',
                'medals',
                'insights'
            )
        );
    }

    public function getUserRankedTimes(string $userUuid): Collection
    {
        return app(PlayerProfile::class)->fetchRuns($userUuid);
    }

    public function deleteUserRankedTimes(string $userUuid)
    {
        // $userTimes = Cache::remember("ranked_times_{$userUuid}", 600, function () use ($userUuid) {
        //     return $this->getUserRankedTimes($userUuid);
        // });

        // $userTimes = collect($userTimes);

        $userTimes = $this->getUserRankedTimes($userUuid);

        // Only rank 1 times
        $rank1Times = $userTimes->where('Rank', 1);
        $filePaths = [];

        foreach ($rank1Times as $time) {
            $filePath = config('replays.path')."/{$time->MapName}/[{$time->CategoryName}].rec";

            if (file_exists($filePath)) {
                $filePaths[] = $filePath;
                unlink($filePath);
            }
        }

        // Delete from DB

        DB::connection('game_mysql')
            ->table('times')
            ->where('user_uuid', $userUuid)
            ->delete();

        // Clear cache so it doesn't serve stale data
        Cache::forget("ranked_times_{$userUuid}");

        /*
        return response()->json([
            "status" => "success",
            "message" => "User times deleted and rank1 files unlinked",
            "deleted_files" => $rank1Times->count(),
            "deleted_paths" => $filePaths
        ]);
        */

        return redirect()
            ->back()
            ->with('status', 'User times deleted and replay files unlinked.');
    }

    public function convertToSteamID64($authId)
    {
        // Parse the STEAM_X:Y:Z parts
        if (preg_match('/^STEAM_[0-5]:([01]):(\d+)$/', $authId, $matches)) {
            $Y = $matches[1];
            $Z = $matches[2];

            return bcadd(bcadd(bcmul($Z, '2'), $Y), '76561197960265728');
        }

        return null; // Invalid format
    }

    public function generatePlayedTimeChartData(
        string $authId,
        int $daysBack = 30
    ): array {
        $startDate = Carbon::now()->subDays($daysBack);
        $days = collect(range(0, $daysBack - 1))
            ->map(
                fn ($i) => Carbon::today()
                    ->subDays($i)
                    ->format('Y-m-d')
            )
            ->reverse()
            ->values()
            ->toArray();

        $sessions = PlayedTimeInfo::where('auth_id', $authId)
            ->where('date_join', '>', $startDate)
            ->orderBy('date_join')
            ->get();

        $grouped = [];

        foreach ($sessions as $session) {
            $day = Carbon::parse($session->date_join)->format('Y-m-d');
            $server = $session->server;
            $key = $server.'_'.$day;

            $time =
                strtotime($session->date_left ?? now()) -
                strtotime($session->date_join);
            if ($time < 0) {
                $time = 0;
            }

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'day' => $day,
                    'server' => $server,
                    'time' => 0,
                ];
            }

            $grouped[$key]['time'] += $time;
        }

        $datasets = [];
        foreach ($grouped as $entry) {
            $stack = $entry['server'];
            $day = $entry['day'];

            if (! isset($datasets[$stack])) {
                $datasets[$stack] = [
                    'label' => $stack,
                    'stack' => $stack,
                    'data' => array_fill(0, count($days), 0),
                ];
            }

            $dayIndex = array_search($day, $days);
            if ($dayIndex !== false) {
                $datasets[$stack]['data'][$dayIndex] = round(
                    $entry['time'] / 60,
                    2
                );
            }
        }

        $chartData = [
            'labels' => $days,
            'datasets' => array_values($datasets),
        ];

        return $chartData;
    }
}
