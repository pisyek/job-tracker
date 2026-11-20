@extends('layouts.app')

@section('title', 'Companies')

@section('content')
    <h2>Companies</h2>

    <p><a href="{{ route('companies.create') }}">Add a company</a></p>

    @if ($companies->isEmpty())
        <p>No companies yet.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Applications</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($companies as $company)
                    <tr>
                        <td><a href="{{ route('companies.show', $company) }}">{{ $company->name }}</a></td>
                        <td>{{ $company->location ?? '-' }}</td>
                        <td>{{ $company->job_applications_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
