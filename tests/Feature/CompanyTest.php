<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the authenticated user their own companies', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('companies.index'))
        ->assertOk()
        ->assertSee($company->name);
});

it('renders a company page owned by the user', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('companies.show', $company))
        ->assertOk()
        ->assertSee($company->name);
});

it('creates a company owned by the authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('companies.store'), [
            'name' => 'Graduate Guild',
            'website' => 'https://example.com',
            'location' => 'Penang',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $company = Company::where('name', 'Graduate Guild')->sole();

    expect($company->user_id)->toBe($user->id)
        ->and($company->website)->toBe('https://example.com')
        ->and($company->location)->toBe('Penang');
});

it('redirects guests away from the company pages', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['companies.index', 'companies.create']);
