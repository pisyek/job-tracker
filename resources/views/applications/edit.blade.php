@extends('layouts.app')

@section('title', 'Edit application')

@section('content')
    <h2>Edit: {{ $application->role_title }}</h2>

    <form method="POST" action="{{ route('applications.update', $application) }}">
        @csrf
        @method('PUT')

        @include('applications._form')

        <button type="submit">Save changes</button>
    </form>

    <p><a href="{{ route('applications.show', $application) }}">Cancel</a></p>
@endsection
