<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the confirm password screen', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('password.confirm'))
        ->assertOk();
});

it('confirms the password when it matches', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();
});

it('rejects a password that does not match', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('password.confirm.store'), ['password' => 'wrong-password'])
        ->assertSessionHasErrorsIn('password');
});
