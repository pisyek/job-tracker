@extends('layouts.app')

@section('title', 'Add an application')

@section('content')
    <flux:heading size="xl">{{ __('Add an application') }}</flux:heading>
    <flux:subheading class="mt-1">{{ __('Track a role you are targeting or have already applied to.') }}</flux:subheading>

    <flux:card class="mt-6 max-w-2xl p-6">
        <form method="POST" action="{{ route('applications.store') }}" class="flex flex-col gap-5">
            @csrf

            @include('applications._form')

            <div class="flex items-center justify-end gap-3">
                <flux:button variant="ghost" :href="route('applications.index')">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:card>
@endsection
