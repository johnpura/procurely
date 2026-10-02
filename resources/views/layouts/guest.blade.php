<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 antialiased">
    <div class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <a href="/" class="text-2xl font-bold tracking-tight text-gray-900">{{ config('app.name') }}</a>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm sm:p-8">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>