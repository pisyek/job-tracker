<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas('registration');
});

it('renders the registration screen', function () {
    $this->get(route('register'))->assertOk();
});

it('registers a new user and redirects to the applications list', function () {
    $this->post(route('register.store'), [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('applications.index', absolute: false));

    $this->assertAuthenticated();
    expect(User::where('email', 'new@example.com')->exists())->toBeTrue();
});

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('register.store'), [
        'name' => 'Another User',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrorsIn('email');

    expect(User::where('email', 'taken@example.com')->count())->toBe(1);
});

it('rejects an unconfirmed password', function () {
    $this->post(route('register.store'), [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'another-password',
    ])->assertSessionHasErrorsIn('password');

    expect(User::where('email', 'new@example.com')->exists())->toBeFalse();
});
