<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompany;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $companies = $request->user()
            ->companies()
            ->withCount('jobApplications')
            ->orderBy('name')
            ->get();

        return view('companies.index', ['companies' => $companies]);
    }

    public function create(): View
    {
        return view('companies.create');
    }

    public function store(StoreCompany $request): RedirectResponse
    {
        $company = $request->user()->companies()->create($request->validated());

        return redirect()
            ->route('companies.show', $company)
            ->with('status', 'Company added.');
    }

    public function show(Company $company): View
    {
        return view('companies.show', ['company' => $company]);
    }
}
