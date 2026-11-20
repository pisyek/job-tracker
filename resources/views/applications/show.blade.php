@extends('layouts.app')

@section('title', $application->role_title)

@section('content')
    <h2>{{ $application->role_title }}</h2>

    <p>
        at <a href="{{ route('companies.show', $application->company) }}">{{ $application->company->name }}</a>
        · <mark>{{ $application->status->label() }}</mark>
    </p>

    <dl>
        <dt>Salary (RM per month)</dt>
        <dd>{{ $application->salary_min ?? '?' }} – {{ $application->salary_max ?? '?' }}</dd>

        <dt>Applied on</dt>
        <dd>{{ $application->applied_on?->format('j M Y') ?? 'Not yet' }}</dd>

        <dt>Notes</dt>
        <dd>{{ $application->notes ?? '-' }}</dd>
    </dl>

    <p><a href="{{ route('applications.edit', $application) }}">Edit</a></p>

    <form method="POST" action="{{ route('applications.destroy', $application) }}">
        @csrf
        @method('DELETE')

        <button type="submit">Delete</button>
    </form>
@endsection
