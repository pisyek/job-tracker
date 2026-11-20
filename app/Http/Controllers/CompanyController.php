<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::withCount('jobApplications')->orderBy('name')->get();

        return view('companies.index', ['companies' => $companies]);
    }

    public function create(): View
    {
        return view('companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:companies,name'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $company = Company::create($validated);

        return redirect()
            ->route('companies.show', $company)
            ->with('status', 'Company added.');
    }

    public function show(Company $company): View
    {
        return view('companies.show', ['company' => $company]);
    }
}
