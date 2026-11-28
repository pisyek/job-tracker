@extends('layouts.app')

@section('title', $company->name)

@section('content')
    <flux:button variant="ghost" :href="route('companies.index')" icon="arrow-left">
        {{ __('Back to companies') }}
    </flux:button>

    <flux:card class="mt-4 p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $company->name }}</flux:heading>
                <flux:text class="mt-1">
                    {{ $company->location ?? __('Location unknown') }}
                    @if ($company->website)
                        · <flux:link :href="$company->website" external>{{ $company->website }}</flux:link>
                    @endif
                </flux:text>
            </div>

            <flux:button variant="primary" :href="route('applications.create')" icon="plus">
                {{ __('Add an application') }}
            </flux:button>
        </div>
    </flux:card>

    <flux:heading class="mt-8">{{ __('Applications') }}</flux:heading>

    @if ($company->jobApplications->isEmpty())
        <x-empty-state
            class="mt-4"
            icon="briefcase"
            :heading="__('No applications for this company yet')"
            :description="__('When you apply somewhere, track it here to follow the progress.')"
        >
            <x-slot name="action">
                <flux:button variant="primary" :href="route('applications.create')" icon="plus">
                    {{ __('Add an application') }}
                </flux:button>
            </x-slot>
        </x-empty-state>
    @else
        <flux:card class="mt-4 overflow-hidden p-0">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Role') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Applied on') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($company->jobApplications as $application)
                        <flux:table.row :key="$application->id">
                            <flux:table.cell>
                                <flux:link :href="route('applications.show', $application)" class="font-medium">
                                    {{ $application->role_title }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell>
                                <x-status-badge :status="$application->status" />
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $application->applied_on?->format('j M Y') ?? '—' }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif
@endsection
