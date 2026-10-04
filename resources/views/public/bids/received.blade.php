@extends('layouts.public')

@section('title', 'Response received')

@section('content')
    <div class="mx-auto max-w-2xl px-4 pt-10 sm:px-6">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Response received</h1>
        <p class="mt-2 text-gray-600">Thank you. Your response to <strong>{{ $bid->reference_number }}</strong> has been recorded.</p>

        <div class="mt-6 rounded-xl border border-green-200 bg-green-50 p-6 text-center">
            <p class="text-sm text-green-800">Your receipt code</p>
            <p class="mt-1 font-mono text-3xl font-bold tracking-widest text-green-900">{{ session('receipt') }}</p>
        </div>

        <p class="mt-4 text-sm text-gray-600">
            @if (session('emailed_to'))
                A copy was sent to {{ session('emailed_to') }}.
            @else
                We could not send a confirmation email, so please write this code down.
            @endif
            Keep the code as your proof of submission. This page will not be shown again.
        </p>

        <a href="{{ route('bids.show', $bid->reference_number) }}" class="mt-6 inline-block text-sm text-brand-500 hover:text-brand-600">Back to the bid</a>
    </div>
@endsection
