<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('redirects guests to the login page', function () {
    $this->get(route('security.edit'))->assertRedirect(route('login'));
});

it('redirects authenticated users without recent password confirmation to the confirmation page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('security.edit'))->assertRedirect(route('password.confirm'));
});

it('renders the security page for a recently password-confirmed user', function () {
    $this->actingAs(User::factory()->create());

    $this->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee('Update password');
});

it('updates the password when the current password is correct', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.security')
        ->set('current_password', 'password')
        ->set('password', 'new-password-12345678')
        ->set('password_confirmation', 'new-password-12345678')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(password_verify('new-password-12345678', $user->fresh()->password))->toBeTrue();
});

it('rejects a password update when the current password is wrong', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.security')
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password-12345678')
        ->set('password_confirmation', 'new-password-12345678')
        ->call('updatePassword')
        ->assertHasErrors('current_password');

    expect(password_verify('password', $user->fresh()->password))->toBeTrue();
});

it('lists the user passkeys and deletes one', function () {
    $user = User::factory()->create();

    $passkey = $user->passkeys()->create([
        'name' => 'Test iPhone',
        'credential_id' => 'test-credential-id',
        'credential' => ['type' => 'public-key', 'id' => 'test-credential-id'],
    ]);

    $this->actingAs($user);

    Livewire::test('pages::settings.security')
        ->assertSee('Test iPhone')
        ->call('confirmDelete', $passkey->id)
        ->call('deletePasskey')
        ->assertSet('showDeleteModal', false);

    expect($user->passkeys()->count())->toBe(0);
});
