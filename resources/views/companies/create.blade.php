@extends('layouts.app')

@section('title', 'Add a company')

@section('content')
    <h2>Add a company</h2>

    <form method="POST" action="{{ route('companies.store') }}">
        @csrf

        <label for="name">Name</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}">
        @error('name') <small>{{ $message }}</small> @enderror

        <label for="website">Website</label>
        <input id="website" name="website" type="url" value="{{ old('website') }}">
        @error('website') <small>{{ $message }}</small> @enderror

        <label for="location">Location</label>
        <input id="location" name="location" type="text" value="{{ old('location') }}">
        @error('location') <small>{{ $message }}</small> @enderror

        <button type="submit">Save</button>
    </form>
@endsection
