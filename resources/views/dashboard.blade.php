@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
            Welcome, {{ auth()->user()->name }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            You are signed in as {{ ucfirst(auth()->user()->role->value) }}.
        </p>
    </div>
@endsection
