<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @php($pageTitle = trim($__env->yieldContent('title')) ?: ($title ?? ''))
    @include('partials.head', ['title' => $pageTitle])
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('applications.index') }}" />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Tracker')" class="grid">
                <flux:sidebar.item icon="briefcase" :href="route('applications.index')" :current="request()->routeIs('applications.*')">
                    {{ __('Applications') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="building-office" :href="route('companies.index')" :current="request()->routeIs('companies.*')">
                    {{ __('Companies') }}
                </flux:sidebar.item>
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />
        <x-user-menu :name="auth()->user()->name" class="hidden lg:block" />
    </flux:sidebar>

    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:spacer />
        <x-user-menu :name="auth()->user()->name" />
    </flux:header>

    <flux:main>
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300">
                {{ session('status') }}
            </div>
        @endif

        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
            <flux:callout icon="envelope" class="mb-6">
                <flux:callout.heading>{{ __('Verify your email address') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Your account works, but verifying your email keeps account recovery reliable.') }}
                </flux:callout.text>
                <x-slot name="actions">
                    <flux:button :href="route('verification.notice')" size="sm" variant="filled">
                        {{ __('Verify now') }}
                    </flux:button>
                </x-slot>
            </flux:callout>
        @endif

        @yield('content')

        {{ $slot ?? '' }}
    </flux:main>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>
</html>
