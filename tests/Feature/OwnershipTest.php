<?php

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('hides other users applications from the index', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    JobApplication::factory()->forUser($alice)->create(['role_title' => 'Alice Role']);
    JobApplication::factory()->forUser($bob)->create(['role_title' => 'Bob Role']);

    $this->actingAs($alice)
        ->get(route('applications.index'))
        ->assertOk()
        ->assertSee('Alice Role')
        ->assertDontSee('Bob Role');
});

it('hides other users companies from the index', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    Company::factory()->for($alice)->create(['name' => 'Alice Corp']);
    Company::factory()->for($bob)->create(['name' => 'Bob Corp']);

    $this->actingAs($alice)
        ->get(route('companies.index'))
        ->assertOk()
        ->assertSee('Alice Corp')
        ->assertDontSee('Bob Corp');
});

it('returns 404 for another users application', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $bobsApplication = JobApplication::factory()->forUser($bob)->create();

    $this->actingAs($alice)->get(route('applications.show', $bobsApplication))->assertNotFound();
    $this->actingAs($alice)->get(route('applications.edit', $bobsApplication))->assertNotFound();
    $this->actingAs($alice)->put(route('applications.update', $bobsApplication), [])->assertNotFound();
    $this->actingAs($alice)->delete(route('applications.destroy', $bobsApplication))->assertNotFound();

    expect($bobsApplication->fresh())->not->toBeNull();
});

it('returns 404 for another users company', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $bobsCompany = Company::factory()->for($bob)->create();

    $this->actingAs($alice)->get(route('companies.show', $bobsCompany))->assertNotFound();
});

it('rejects another users company id when storing an application', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $bobsCompany = Company::factory()->for($bob)->create();

    $this->actingAs($alice)
        ->post(route('applications.store'), [
            'company_id' => $bobsCompany->id,
            'role_title' => 'Sneaky Role',
            'status' => 'wishlist',
        ])
        ->assertSessionHasErrors('company_id');

    expect(JobApplication::where('role_title', 'Sneaky Role')->exists())->toBeFalse();
});

it('allows the same company name for different users', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    Company::factory()->for($alice)->create(['name' => 'Acme']);

    $this->actingAs($bob)
        ->post(route('companies.store'), ['name' => 'Acme'])
        ->assertSessionHasNoErrors();

    expect(Company::where('name', 'Acme')->count())->toBe(2);
});

it('rejects a duplicate company name for the same user', function () {
    $alice = User::factory()->create();

    Company::factory()->for($alice)->create(['name' => 'Acme']);

    $this->actingAs($alice)
        ->post(route('companies.store'), ['name' => 'Acme'])
        ->assertSessionHasErrors('name');
});

it('never writes an orphan row through the app', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();

    $this->actingAs($user)->post(route('applications.store'), [
        'company_id' => $company->id,
        'role_title' => 'Backend Developer',
        'status' => 'applied',
    ])->assertSessionHasNoErrors();

    expect(Company::whereNull('user_id')->count())->toBe(0)
        ->and(JobApplication::whereNull('user_id')->count())->toBe(0);
});
