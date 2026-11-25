@extends('layouts.app')

@section('title', $company->name)

@section('content')
    <h2>{{ $company->name }}</h2>

    <p>
        {{ $company->location ?? 'Location unknown' }}
        @if ($company->website)
            · <a href="{{ $company->website }}">{{ $company->website }}</a>
        @endif
    </p>

    <h3>Applications</h3>

    @forelse ($company->jobApplications as $application)
        <p>
            <a href="{{ route('applications.show', $application) }}">{{ $application->role_title }}</a>
            · {{ $application->status->label() }}
        </p>
    @empty
        <p>No applications for this company yet.</p>
    @endforelse

    <p><a href="{{ route('applications.create') }}">Add an application</a></p>
@endsection
