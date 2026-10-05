<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Bids') | {{ config('procurely.organization') }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')
</head>
<body class="flex min-h-screen flex-col bg-white text-gray-800 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:px-4 focus:py-2 focus:text-brand-500">Skip to main content</a>

    <header class="border-b border-gray-200">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="/images/logo/procurely-icon.svg" alt="" class="h-9 w-9">
                <span class="leading-tight">
                    <span class="block text-lg font-bold tracking-tight text-gray-900">Procurely</span>
                    <span class="block text-xs text-gray-500">{{ config('procurely.organization') }} &middot; {{ config('procurely.department') }}</span>
                </span>
            </a>

            <nav class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm" aria-label="Main">
                @foreach ([['bids.open', 'Open bids'], ['bids.closed', 'Closed bids'], ['bids.search', 'Search bids']] as [$route, $label])
                    <a href="{{ route($route) }}" 
                    @if (request()->routeIs($route)) aria-current="page" @endif
                    @class([
                        'font-medium text-brand-500' => request()->routeIs($route),
                        'text-gray-600 hover:text-gray-900' => ! request()->routeIs($route),
                    ])>{{ $label }}</a>
                @endforeach

                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-brand-500 px-4 py-2 font-medium text-white hover:bg-brand-600">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg border border-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-50">Staff sign in</a>
                @endauth
            </nav>
        </div>
    </header>

    <main id="main" tabindex="-1" class="flex-1">
        @yield('content')
    </main>

    <footer class="mt-16 border-t border-gray-200 bg-gray-50">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 py-8 text-sm text-gray-600 sm:px-6 md:grid-cols-2">
            <div>
                <p class="font-semibold text-gray-900">{{ config('procurely.organization') }} &middot; {{ config('procurely.department') }}</p>
                <p class="mt-1">{{ config('procurely.contact.address') }}</p>
            </div>
            <div class="md:text-right">
                <p>{{ config('procurely.contact.phone') }}</p>
                <p><a href="mailto:{{ config('procurely.contact.email') }}" class="text-brand-500 hover:text-brand-600">{{ config('procurely.contact.email') }}</a></p>
            </div>
        </div>
    </footer>
</body>
</html>