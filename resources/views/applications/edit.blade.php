@extends('layouts.app')

@section('title', 'Edit application')

@section('content')
    <flux:heading size="xl">{{ __('Edit') }}: {{ $application->role_title }}</flux:heading>
    <flux:subheading class="mt-1">{{ __('Update the details of this application.') }}</flux:subheading>

    <flux:card class="mt-6 max-w-2xl p-6">
        <form method="POST" action="{{ route('applications.update', $application) }}" class="flex flex-col gap-5">
            @csrf
            @method('PUT')

            @include('applications._form')

            <div class="flex items-center justify-end gap-3">
                <flux:button variant="ghost" :href="route('applications.show', $application)">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            </div>
        </form>
    </flux:card>
@endsection
