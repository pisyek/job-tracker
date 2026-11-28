<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Requests\StoreJobApplication;
use App\Http\Requests\UpdateJobApplication;
use App\Models\JobApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $applications = $request->user()
            ->jobApplications()
            ->with('company')
            ->latest()
            ->get();

        return view('applications.index', ['applications' => $applications]);
    }

    public function create(Request $request): View
    {
        return view('applications.create', [
            'application' => new JobApplication(['status' => ApplicationStatus::Wishlist]),
            'companies' => $request->user()->companies()->orderBy('name')->get(),
            'statuses' => ApplicationStatus::cases(),
        ]);
    }

    public function store(StoreJobApplication $request): RedirectResponse
    {
        $application = $request->user()->jobApplications()->create($request->validated());

        return redirect()
            ->route('applications.show', $application)
            ->with('status', 'Application added.');
    }

    public function show(JobApplication $application): View
    {
        return view('applications.show', ['application' => $application]);
    }

    public function edit(Request $request, JobApplication $application): View
    {
        return view('applications.edit', [
            'application' => $application,
            'companies' => $request->user()->companies()->orderBy('name')->get(),
            'statuses' => ApplicationStatus::cases(),
        ]);
    }

    public function update(UpdateJobApplication $request, JobApplication $application): RedirectResponse
    {
        $application->update($request->validated());

        return redirect()
            ->route('applications.show', $application)
            ->with('status', 'Application updated.');
    }

    public function destroy(JobApplication $application): RedirectResponse
    {
        $application->delete();

        return redirect()
            ->route('applications.index')
            ->with('status', 'Application deleted.');
    }
}
