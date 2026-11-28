<?php

use App\Enums\ApplicationStatus;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to view applications', function () {
    $this->get(route('applications.index'))
        ->assertRedirect(route('login'));
});

it('shows a job application with its company', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->get(route('applications.show', $application))
        ->assertOk()
        ->assertSee($application->role_title)
        ->assertSee($application->company->name);
});

it('stores a new job application for the acting user', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(route('applications.store'), [
        'company_id' => $company->id,
        'role_title' => 'Backend Engineer',
        'status' => ApplicationStatus::Applied->value,
        'applied_on' => '2026-11-01',
    ]);

    $this->assertDatabaseHas('job_applications', [
        'user_id' => $user->id,
        'company_id' => $company->id,
        'role_title' => 'Backend Engineer',
        'status' => 'applied',
    ]);

    $response->assertRedirect();
});

it('rejects an application without a company or role title', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('applications.store'), [
        'status' => ApplicationStatus::Wishlist->value,
    ])->assertSessionHasErrors(['company_id', 'role_title']);
});

it('rejects a company owned by another user', function () {
    $user = User::factory()->create();
    $foreignCompany = Company::factory()->create();

    $this->actingAs($user)->post(route('applications.store'), [
        'company_id' => $foreignCompany->id,
        'role_title' => 'Backend Engineer',
        'status' => ApplicationStatus::Wishlist->value,
    ])->assertSessionHasErrors('company_id');
});

it('counts the job applications belonging to a company', function () {
    $company = Company::factory()->create();
    JobApplication::factory()->count(2)->for($company)->create();

    expect($company->jobApplications()->count())->toBe(2);
});
