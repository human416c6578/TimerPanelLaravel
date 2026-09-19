<?php

use App\Livewire\GlobalSearch;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

test('nothing is searched until there are two characters', function () {
    // A single letter would match half the players, and needs no database round trip to refuse.
    Livewire::test(GlobalSearch::class)
        ->set('q', 'a')
        ->assertDontSee('Nothing found')
        ->assertSet('q', 'a');
});

test('it offers the placeholder it was given', function () {
    Livewire::test(GlobalSearch::class, ['placeholder' => 'Find a player'])
        ->assertSeeHtml('placeholder="Find a player"');
});

test('the player being compared against cannot be swapped from the browser', function () {
    Livewire::test(GlobalSearch::class, ['compareWith' => 'u1'])
        ->set('compareWith', 'u2');
})->throws(CannotUpdateLockedPropertyException::class);
