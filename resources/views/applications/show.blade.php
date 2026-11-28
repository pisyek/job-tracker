@extends('layouts.app')

@section('title', $application->role_title)

@section('content')
    <flux:button variant="ghost" :href="route('applications.index')" icon="arrow-left">
        {{ __('Back to applications') }}
    </flux:button>

    <flux:card class="mt-4 p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $application->role_title }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('at') }}
                    <flux:link :href="route('companies.show', $application->company)">{{ $application->company->name }}</flux:link>
                </flux:text>
            </div>

            <x-status-badge :status="$application->status" />
        </div>

        <dl class="mt-8 grid gap-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Salary (RM per month)') }}</dt>
                <dd class="mt-1">{{ $application->salary_min ?? '?' }} – {{ $application->salary_max ?? '?' }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Applied on') }}</dt>
                <dd class="mt-1">{{ $application->applied_on?->format('j M Y') ?? __('Not yet') }}</dd>
            </div>

            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Notes') }}</dt>
                <dd class="mt-1 whitespace-pre-line">{{ $application->notes ?? '—' }}</dd>
            </div>
        </dl>
    </flux:card>

    <div class="mt-6 flex items-center gap-3">
        <flux:button variant="primary" :href="route('applications.edit', $application)" icon="pencil">
            {{ __('Edit') }}
        </flux:button>

        <form method="POST" action="{{ route('applications.destroy', $application) }}" onsubmit="return confirm('Delete this application?')">
            @csrf
            @method('DELETE')

            <flux:button type="submit" variant="danger" icon="trash">{{ __('Delete') }}</flux:button>
        </form>
    </div>
@endsection
