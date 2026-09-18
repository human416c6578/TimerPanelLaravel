<?php

use Livewire\Volt\Volt;

test('the registration screen redirects to login', function () {
    // Public registration is disabled: routes/auth.php redirects /register to
    // /login and never names the route, so Route::has('register') is false.
    $response = $this->get('/register');

    $response->assertRedirect('/login');
});

test('new users can register', function () {
    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
