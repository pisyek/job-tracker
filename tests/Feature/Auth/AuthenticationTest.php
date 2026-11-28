<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the login screen', function () {
    $this->get(route('login'))->assertOk();
});

it('authenticates a user and redirects to the applications list', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('applications.index', absolute: false));

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

it('logs out and redirects home', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('home', absolute: false));

    $this->assertGuest();
});

it('redirects guests from the tracker to the login page', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['applications.index', 'applications.create', 'companies.index', 'companies.create']);
