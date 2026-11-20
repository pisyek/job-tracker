<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Requests\StoreJobApplication;
use App\Models\Company;
use App\Models\JobApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobApplicationController extends Controller
{
    public function index(): View
    {
        $applications = JobApplication::with('company')->latest()->get();

        return view('applications.index', ['applications' => $applications]);
    }

    public function create(): View
    {
        return view('applications.create', [
            'application' => new JobApplication(['status' => ApplicationStatus::Wishlist]),
            'companies' => Company::orderBy('name')->get(),
            'statuses' => ApplicationStatus::cases(),
        ]);
    }

    public function store(StoreJobApplication $request): RedirectResponse
    {
        $application = JobApplication::create($request->validated());

        return redirect()
            ->route('applications.show', $application)
            ->with('status', 'Application added.');
    }

    public function show(JobApplication $application): View
    {
        return view('applications.show', ['application' => $application]);
    }

    public function edit(JobApplication $application): View
    {
        return view('applications.edit', [
            'application' => $application,
            'companies' => Company::orderBy('name')->get(),
            'statuses' => ApplicationStatus::cases(),
        ]);
    }

    public function update(Request $request, JobApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'role_title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'gte:salary_min'],
            'applied_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $application->update($validated);

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
