<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <meta name="description" content="Procurely is the internal portal for purchase requests, approvals and suppliers.">
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-800 antialiased">

    <header class="border-b border-gray-100">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="/images/logo/procurely-icon.svg" alt="" class="h-9 w-9">
                <span class="text-xl font-bold tracking-tight text-gray-900">Procurely</span>
            </a>
            <nav class="flex items-center gap-6 text-sm">
                <a href="#features" class="hidden text-gray-600 hover:text-gray-900 sm:inline">Features</a>
                <a href="#how" class="hidden text-gray-600 hover:text-gray-900 sm:inline">How it works</a>
                <a href="{{ route('login') }}" class="rounded-lg bg-brand-500 px-4 py-2 font-medium text-white hover:bg-brand-600">Sign in</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:py-24">
            <div>
                <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl" style="text-wrap: balance">
                    Every purchase request, approval and supplier in one place.
                </h1>
                <p class="mt-5 max-w-xl text-lg text-gray-600">
                    Procurely is the internal portal for our purchasing team. Staff raise requests, administrators review them, and everyone can see where things stand.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-4">
                    <a href="{{ route('login') }}" class="rounded-lg bg-brand-500 px-6 py-3 text-sm font-medium text-white hover:bg-brand-600">Sign in</a>
                    <span class="text-sm text-gray-500">Access is by invitation. Ask an administrator for an account.</span>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5 sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700">Recent requests</h2>
                    <span class="text-xs uppercase tracking-wide text-gray-400">Example data</span>
                </div>
                <ul class="divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white">
                    @foreach ([
                        ['Office chairs, 6 units', 'Pending', 'bg-amber-50 text-amber-700'],
                        ['Laptop docking stations', 'Approved', 'bg-green-50 text-green-700'],
                        ['Printer toner, 4 cartridges', 'Ordered', 'bg-blue-50 text-blue-700'],
                    ] as [$item, $status, $classes])
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <span class="min-w-0 truncate text-sm text-gray-800">{{ $item }}</span>
                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">{{ $status }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section id="features" class="border-t border-gray-100 bg-gray-50">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">What you can do</h2>
                <div class="mt-8 grid gap-x-10 gap-y-8 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['Raise requests', 'Describe what you need, how many, and why. No more email threads.'],
                        ['Get approvals', 'Administrators review each request and approve or reject it with a note.'],
                        ['Track suppliers', 'Keep supplier details and history where the whole team can find them.'],
                        ['Right access for each person', 'Administrators manage the team. Staff see what they need to do their job.'],
                    ] as [$title, $text])
                        <div class="border-t-2 border-brand-500 pt-4">
                            <h3 class="font-semibold text-gray-900">{{ $title }}</h3>
                            <p class="mt-2 text-sm text-gray-600">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="how" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">How it works</h2>
            <ol class="mt-8 grid gap-8 md:grid-cols-3">
                @foreach ([
                    ['Request', 'A staff member submits what they need.'],
                    ['Review', 'An administrator approves or rejects it.'],
                    ['Order and track', 'Approved items are ordered and followed through to delivery.'],
                ] as $i => [$title, $text])
                    <li class="flex gap-4">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-500">{{ $i + 1 }}</span>
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $title }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ $text }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="border-t border-gray-100 bg-gray-50">
            <div class="mx-auto flex max-w-6xl flex-col items-start justify-between gap-4 px-4 py-12 sm:flex-row sm:items-center sm:px-6">
                <p class="text-lg font-semibold text-gray-900">Already have an account?</p>
                <a href="{{ route('login') }}" class="rounded-lg bg-brand-500 px-6 py-3 text-sm font-medium text-white hover:bg-brand-600">Sign in</a>
            </div>
        </section>
    </main>

    <footer class="border-t border-gray-100">
        <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-gray-500 sm:px-6">
            &copy; {{ date('Y') }} Procurely
        </div>
    </footer>
</body>
</html>