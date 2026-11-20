@extends('layouts.app')

@section('title', 'Add an application')

@section('content')
    <h2>Add an application</h2>

    <form method="POST" action="{{ route('applications.store') }}">
        @csrf

        @include('applications._form')

        <button type="submit">Save</button>
    </form>
@endsection
