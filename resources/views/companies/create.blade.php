@extends('layouts.app')

@section('title', 'Add a company')

@section('content')
    <flux:heading size="xl">{{ __('Add a company') }}</flux:heading>
    <flux:subheading class="mt-1">{{ __('Companies you can attach job applications to.') }}</flux:subheading>

    <flux:card class="mt-6 max-w-xl p-6">
        <form method="POST" action="{{ route('companies.store') }}" class="flex flex-col gap-5">
            @csrf

            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                :placeholder="__('e.g. Acme Sdn Bhd')"
            />

            <flux:input
                name="website"
                :label="__('Website')"
                :value="old('website')"
                type="url"
                placeholder="https://example.com"
            />

            <flux:input
                name="location"
                :label="__('Location')"
                :value="old('location')"
                type="text"
                :placeholder="__('e.g. Kuala Lumpur')"
            />

            <div class="flex items-center justify-end gap-3">
                <flux:button variant="ghost" :href="route('companies.index')">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:card>
@endsection
