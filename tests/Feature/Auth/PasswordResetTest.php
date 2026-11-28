<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->skipUnlessFortifyHas('reset-passwords');
});

it('renders the forgot password screen', function () {
    $this->get(route('password.request'))->assertOk();
});

it('emails a reset link to the user', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('renders the reset password screen from the notification link', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->get(route('password.reset', $notification->token).'?email='.$user->email)
            ->assertOk()
            ->assertSee($user->email);

        return true;
    });
});

it('updates the password when given a valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        expect(Hash::check('a-brand-new-password', $user->fresh()->password))->toBeTrue();

        return true;
    });
});

it('leaves the password unchanged when given an invalid token', function () {
    $user = User::factory()->create();

    $this->post(route('password.update'), [
        'token' => 'not-a-real-token',
        'email' => $user->email,
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ])->assertSessionHasErrorsIn('email');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
