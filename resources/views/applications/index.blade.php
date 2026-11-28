@extends('layouts.app')

@section('title', 'Applications')

@php
    $interviewingCount = $applications->where('status', \App\Enums\ApplicationStatus::Interviewing)->count();
    $offerCount = $applications->where('status', \App\Enums\ApplicationStatus::Offer)->count();
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Applications') }}</flux:heading>
            <flux:subheading>{{ __('Everything you have applied to, newest first.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('applications.create')" icon="plus">
            {{ __('Add application') }}
        </flux:button>
    </div>

    @if ($applications->isEmpty())
        <div class="mt-8">
            <x-empty-state
                icon="briefcase"
                :heading="__('No applications yet')"
                :description="__('Track your first job application and its progress from wishlist to offer.')"
            >
                <x-slot name="action">
                    <flux:button variant="primary" :href="route('applications.create')" icon="plus">
                        {{ __('Add application') }}
                    </flux:button>
                </x-slot>
            </x-empty-state>
        </div>
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <flux:card class="p-5">
                <flux:heading size="xl">{{ $applications->count() }}</flux:heading>
                <flux:subheading>{{ __('Total applications') }}</flux:subheading>
            </flux:card>

            <flux:card class="p-5">
                <flux:heading size="xl" class="text-amber-600 dark:text-amber-400">{{ $interviewingCount }}</flux:heading>
                <flux:subheading>{{ __('Interviewing') }}</flux:subheading>
            </flux:card>

            <flux:card class="p-5">
                <flux:heading size="xl" class="text-green-600 dark:text-green-400">{{ $offerCount }}</flux:heading>
                <flux:subheading>{{ __('Offers') }}</flux:subheading>
            </flux:card>
        </div>

        <flux:card class="mt-6 overflow-hidden p-0">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Role') }}</flux:table.column>
                    <flux:table.column>{{ __('Company') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Applied on') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($applications as $application)
                        <flux:table.row :key="$application->id">
                            <flux:table.cell>
                                <flux:link :href="route('applications.show', $application)" class="font-medium">
                                    {{ $application->role_title }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell>{{ $application->company->name }}</flux:table.cell>
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
