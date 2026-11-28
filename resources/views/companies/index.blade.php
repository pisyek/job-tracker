@extends('layouts.app')

@section('title', 'Companies')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Companies') }}</flux:heading>
            <flux:subheading>{{ __('The employers you are tracking applications against.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('companies.create')" icon="plus">
            {{ __('Add a company') }}
        </flux:button>
    </div>

    @if ($companies->isEmpty())
        <div class="mt-8">
            <x-empty-state
                icon="building-office"
                :heading="__('No companies yet')"
                :description="__('Add a company first, then you can attach job applications to it.')"
            >
                <x-slot name="action">
                    <flux:button variant="primary" :href="route('companies.create')" icon="plus">
                        {{ __('Add a company') }}
                    </flux:button>
                </x-slot>
            </x-empty-state>
        </div>
    @else
        <flux:card class="mt-6 overflow-hidden p-0">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Location') }}</flux:table.column>
                    <flux:table.column>{{ __('Applications') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($companies as $company)
                        <flux:table.row :key="$company->id">
                            <flux:table.cell>
                                <flux:link :href="route('companies.show', $company)" class="font-medium">
                                    {{ $company->name }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell>{{ $company->location ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $company->job_applications_count }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif
@endsection
