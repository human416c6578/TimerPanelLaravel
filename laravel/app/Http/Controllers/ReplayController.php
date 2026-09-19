<?php

namespace App\Http\Controllers;

use App\Models\Time;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReplayController extends Controller
{
    public function index(): View
    {
        return view('replays.list');
    }

    /**
     * The old replay URL named a map and a category and meant "whoever holds
     * the record". A run has its own page now, so send the old links there.
     */
    public function show(string $map, int $category): RedirectResponse
    {
        $record = Time::where('map_uuid', $map)
            ->where('category_id', $category)
            ->orderBy('time')
            ->firstOrFail();

        return redirect()->route('runs.show', [$map, $category, $record->user_uuid], 301);
    }
}
