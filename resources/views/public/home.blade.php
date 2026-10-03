@extends('layouts.public')

@section('title', 'Procurement')

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:py-20">
        <h1 class="max-w-3xl text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl" style="text-wrap: balance">
            Bids and requests for proposals from {{ config('procurely.organization') }}
        </h1>
        <p class="mt-5 max-w-2xl text-lg text-gray-600">
            The {{ config('procurely.department') }} department posts every open solicitation here. Browse what is open, review past bids and their awards, or search by keyword.
        </p>

        <div class="mt-10 grid gap-4 md:grid-cols-3">
            <a href="{{ route('bids.open') }}" class="rounded-xl border border-gray-200 p-5 hover:border-brand-500">
                <p class="text-3xl font-bold text-brand-500">{{ $openCount }}</p>
                <p class="mt-1 font-semibold text-gray-900">Open bids</p>
                <p class="mt-1 text-sm text-gray-600">Currently accepting responses.</p>
            </a>
            <a href="{{ route('bids.closed') }}" class="rounded-xl border border-gray-200 p-5 hover:border-brand-500">
                <p class="text-3xl font-bold text-gray-900">{{ $closedCount }}</p>
                <p class="mt-1 font-semibold text-gray-900">Closed bids</p>
                <p class="mt-1 text-sm text-gray-600">Past bids and award results.</p>
            </a>
            <a href="{{ route('bids.search') }}" class="rounded-xl border border-gray-200 p-5 hover:border-brand-500">
                <p class="text-3xl font-bold text-gray-900">Search</p>
                <p class="mt-1 font-semibold text-gray-900">Find a bid</p>
                <p class="mt-1 text-sm text-gray-600">By keyword, department, status or date.</p>
            </a>
        </div>
    </section>

    @if ($latest->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mb-4 flex items-baseline justify-between">
                <h2 class="text-xl font-bold tracking-tight text-gray-900">Closing soonest</h2>
                <a href="{{ route('bids.open') }}" class="text-sm text-brand-500 hover:text-brand-600">All open bids</a>
            </div>
            @include('public.bids._table', ['bids' => $latest])
        </section>
    @endif

    <section class="mx-auto mt-16 max-w-6xl px-4 sm:px-6">
        <div class="grid gap-x-12 gap-y-8 md:grid-cols-2">
            <div>
                <h2 class="font-semibold text-gray-900">About</h2>
                <p class="mt-2 text-sm text-gray-600">The {{ config('procurely.department') }} department buys goods and services for {{ config('procurely.organization') }}. Opportunities are posted here so any qualified vendor can find them and respond.</p>
            </div>
            <div>
                <h2 class="font-semibold text-gray-900">Responding to a bid</h2>
                <p class="mt-2 text-sm text-gray-600">Responses may be submitted online through this site, by email, by mail, or in person. Each bid page lists its contact, closing date and time. Responses must be received before the closing time.</p>
            </div>
            <div>
                <h2 class="font-semibold text-gray-900">Bid award</h2>
                <p class="mt-2 text-sm text-gray-600">Awards follow the criteria stated in each solicitation. Results are posted with the bid on the closed bids page.</p>
            </div>
            <div>
                <h2 class="font-semibold text-gray-900">Questions and protests</h2>
                <p class="mt-2 text-sm text-gray-600">Direct questions about a bid, or concerns about an award, to the contact listed on that bid.</p>
            </div>
            <div class="md:col-span-2">
                <h2 class="font-semibold text-gray-900">Before you start work</h2>
                <p class="mt-2 text-sm text-gray-600">Vendors must not perform services or deliver goods until they have received a fully executed contract.</p>
            </div>
        </div>
    </section>
@endsection