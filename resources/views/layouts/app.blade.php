<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Job Tracker')</title>
    <link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
</head>
<body>
    <header>
        <h1>Job Tracker</h1>
        <nav>
            <a href="{{ route('applications.index') }}">Applications</a>
            <a href="{{ route('companies.index') }}">Companies</a>
        </nav>
    </header>

    <main>
        @if (session('status'))
            <p class="notice">{{ session('status') }}</p>
        @endif

        @yield('content')
    </main>
</body>
</html>