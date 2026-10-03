@extends('layouts.app')

@section('title', 'New bid')

@section('content')
    <div class="max-w-3xl rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90">New bid</h3>
        <form method="POST" action="{{ route('manage.bids.store') }}" class="space-y-4">
            @csrf
            @include('manage.bids.partials.form')
        </form>
    </div>
@endsection