@extends('layouts.app')

@section('title', 'Applications')

@section('content')
    <h2>Applications</h2>

    <p><a href="{{ route('applications.create') }}">Add an application</a></p>

    @if ($applications->isEmpty())
        <p>No applications yet.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Company</th>
                    <th>Status</th>
                    <th>Applied on</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($applications as $application)
                    <tr>
                        <td><a href="{{ route('applications.show', $application) }}">{{ $application->role_title }}</a></td>
                        <td>{{ $application->company->name }}</td>
                        <td>{{ $application->status->label() }}</td>
                        <td>{{ $application->applied_on?->format('j M Y') ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
