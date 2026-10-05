@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $card = 'rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]';
        $tiles = [
            ['label' => $isAdmin ? 'Drafts' : 'My drafts', 'value' => $stats['drafts'], 'href' => route('manage.bids.index', array_filter(['status' => 'draft', 'mine' => $isAdmin ? null : 1]))],
            ['label' => $isAdmin ? 'Open bids' : 'My open bids', 'value' => $stats['open'], 'href' => $isAdmin ? route('manage.bids.index', ['status' => 'open']) : null],
            ['label' => 'Closing in 7 days', 'value' => $stats['closing_soon'], 'href' => $isAdmin ? route('manage.bids.index', ['status' => 'open']) : null],
            ['label' => $isAdmin ? 'Awaiting award' : 'Awaiting my review', 'value' => $stats['awaiting'], 'href' => $isAdmin ? route('manage.bids.index', ['status' => 'closed']) : null],
        ];
        if ($isAdmin) {
            $tiles[] = ['label' => 'Active users', 'value' => $stats['users'], 'href' => route('staff.index', ['filter' => 'active'])];
        }
    @endphp

    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">Welcome, {{ auth()->user()->name }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Signed in as {{ ucfirst(auth()->user()->role->value) }}. Here is what needs attention.</p>
    </div>

    @if ($unassigned > 0)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            {{ $unassigned }} open {{ \Illuminate\Support\Str::plural('bid', $unassigned) }} {{ $unassigned === 1 ? 'has' : 'have' }} no assigned contact, so vendors see the department default.
            <a href="{{ route('manage.bids.index', ['status' => 'open']) }}" class="font-medium underline">Review open bids</a>
        </div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-{{ $isAdmin ? 5 : 4 }}">
        @foreach ($tiles as $tile)
            @if ($tile['href'])
                <a href="{{ $tile['href'] }}" class="{{ $card }} block p-5 hover:border-brand-500">
            @else
                <div class="{{ $card }} p-5">
            @endif
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $tile['label'] }}</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white/90">{{ $tile['value'] }}</p>
            @if ($tile['href'])
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="{{ $card }} p-6">
            <h3 class="mb-3 font-semibold text-gray-800 dark:text-white/90">{{ $isAdmin ? 'Drafts to review' : 'My drafts' }}</h3>
            @forelse ($draftList as $bid)
                <a href="{{ route('manage.bids.edit', $bid) }}" class="flex items-center justify-between gap-3 border-b border-gray-100 py-2 text-sm hover:text-brand-500 dark:border-gray-800">
                    <span class="min-w-0 truncate">{{ $bid->reference_number }} &middot; {{ $bid->title }}</span>
                    <span class="shrink-0 text-gray-500">{{ $isAdmin ? ($bid->creator?->name ?? '-') : $bid->updated_at->diffForHumans() }}</span>
                </a>
            @empty
                <p class="text-sm text-gray-500">Nothing here. <a href="{{ route('manage.bids.create') }}" class="text-brand-500">Start a new bid</a>.</p>
            @endforelse
        </section>

        <section class="{{ $card }} p-6">
            <h3 class="mb-3 font-semibold text-gray-800 dark:text-white/90">Closing in the next 7 days</h3>
            @forelse ($closingList as $bid)
                <a href="{{ route('manage.bids.edit', $bid) }}" class="flex items-center justify-between gap-3 border-b border-gray-100 py-2 text-sm hover:text-brand-500 dark:border-gray-800">
                    <span class="min-w-0 truncate">{{ $bid->reference_number }} &middot; {{ $bid->title }}</span>
                    <span class="shrink-0 text-gray-500">{{ $bid->closes_at->diffForHumans() }} &middot; {{ $bid->responses_count }} {{ \Illuminate\Support\Str::plural('response', $bid->responses_count) }}</span>
                </a>
            @empty
                <p class="text-sm text-gray-500">No bids are closing this week.</p>
            @endforelse
        </section>

        <section class="{{ $card }} p-6 lg:col-span-2">
            <h3 class="mb-3 font-semibold text-gray-800 dark:text-white/90">{{ $isAdmin ? 'Closed and waiting for an award' : 'Closed and waiting for your review' }}</h3>
            @forelse ($awaitingList as $bid)
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 py-2 text-sm dark:border-gray-800">
                    <a href="{{ route('manage.bids.edit', $bid) }}" class="min-w-0 truncate hover:text-brand-500">{{ $bid->reference_number }} &middot; {{ $bid->title }}</a>
                    <span class="flex shrink-0 items-center gap-4 text-gray-500">
                        <span>closed {{ $bid->closes_at->diffForHumans() }}</span>
                        <a href="{{ route('manage.bids.responses.index', $bid) }}" class="text-brand-500 hover:text-brand-600">
                            Open {{ $bid->responses_count }} {{ \Illuminate\Support\Str::plural('response', $bid->responses_count) }}
                        </a>
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-500">Nothing is waiting.</p>
            @endforelse
        </section>

        @if ($isAdmin)
            <section class="{{ $card }} p-6 lg:col-span-2">
                <div class="mb-3 flex items-baseline justify-between">
                    <h3 class="font-semibold text-gray-800 dark:text-white/90">Recent activity</h3>
                    <a href="{{ route('audit.index') }}" class="text-sm text-brand-500 hover:text-brand-600">Full audit log</a>
                </div>
                @forelse ($activity as $log)
                    <div class="flex flex-wrap justify-between gap-2 border-b border-gray-100 py-2 text-sm dark:border-gray-800">
                        <span class="min-w-0">{{ $log->summary }}</span>
                        <span class="shrink-0 text-gray-500">{{ $log->actor_name ?? 'System' }} &middot; {{ $log->occurred_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No activity recorded yet.</p>
                @endforelse
            </section>
        @endif
    </div>
@endsection