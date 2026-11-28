<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
{{-- Keep the app dark regardless of system preference (the plan's "dark theme" decision); --}}
{{-- remove this line if an appearance switcher is ever added. --}}
<script>window.Flux && window.Flux.applyAppearance('dark')</script>
