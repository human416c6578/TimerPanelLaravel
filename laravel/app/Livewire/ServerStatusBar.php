<?php

namespace App\Livewire;

use App\Services\ServerStatus;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/**
 * The live servers, in the footer of every page. Lazy on purpose: a cold cache
 * means up to four UDP queries in a row, and that must never hold up the page
 * the visitor actually asked for.
 */
#[Lazy]
class ServerStatusBar extends Component
{
    public function placeholder(): View
    {
        return view('livewire.server-status-bar-placeholder');
    }

    public function render(ServerStatus $status): View
    {
        $servers = $status->all();

        return view('livewire.server-status-bar', [
            'servers' => $servers,
            'online' => $servers->where('online', true),
        ]);
    }
}
