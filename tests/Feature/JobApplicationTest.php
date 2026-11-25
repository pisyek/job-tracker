<?php

use App\Models\Company;
use App\Models\JobApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows a job application with its company', function () {
    $application = JobApplication::factory()->create();

    $this->get(route('applications.show', $application))
        ->assertOk()
        ->assertSee($application->role_title)
        ->assertSee($application->company->name);
});

it('counts the job applications belonging to a company', function () {
    $company = Company::factory()->create();
    JobApplication::factory()->count(2)->for($company)->create();

    expect($company->jobApplications()->count())->toBe(2);
});
