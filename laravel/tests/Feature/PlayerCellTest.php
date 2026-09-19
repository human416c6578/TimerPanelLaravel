<?php

test('a player with a Steam picture shows it', function () {
    $this->blade('<x-ui.player name="Rocket" uuid="u1" nationality="ro" avatar="https://cdn.example/a.jpg" />')
        ->assertSee('https://cdn.example/a.jpg', false)
        ->assertSee('Rocket');
});

test('a player without one gets their initial, so rows still line up', function () {
    $this->blade('<x-ui.player name="rocket" uuid="u1" />')
        ->assertDontSee('<img', false)
        ->assertSee('r');
});

test('the country flag only renders for a real two-letter code', function () {
    $this->blade('<x-ui.player name="A" nationality="ro" />')->assertSee('flagcdn.com/16x12/ro.png', false);
    $this->blade('<x-ui.player name="A" nationality="../x" />')->assertDontSee('flagcdn', false);
});
