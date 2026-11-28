<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects to login when there is no pending two factor challenge', function () {
    $this->get(route('two-factor.login'))->assertRedirect(route('login'));
});

it('redirects users with two factor authentication to the challenge after login', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login', absolute: false));

    $this->assertGuest();
});

it('shows the challenge page for a pending two factor login', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->get(route('two-factor.login'))
        ->assertOk()
        ->assertSee('Authentication code');
});

it('authenticates with a recovery code and consumes it', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->post(route('two-factor.login.store'), [
        'recovery_code' => 'recovery-code-1',
    ])->assertRedirect(route('applications.index', absolute: false));

    $this->assertAuthenticatedAs($user);

    $remaining = json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true);

    expect($remaining)->not->toContain('recovery-code-1');
});

it('keeps the user a guest when the recovery code is wrong', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->post(route('two-factor.login.store'), [
        'recovery_code' => 'not-a-real-code',
    ])
        ->assertRedirect(route('two-factor.login', absolute: false))
        ->assertSessionHasErrors('recovery_code');

    $this->assertGuest();
});
