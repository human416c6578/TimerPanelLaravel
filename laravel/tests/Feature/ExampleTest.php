<?php

test('the home page renders', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
})->skip(fn () => ! gameDatabaseIsAvailable(), 'game database not reachable');
