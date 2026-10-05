@extends('layouts.app')

@section('title', 'Audit log')

@section('content')
    @php
        $input = 'h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
        $label = 'mb-1 block text-xs font-medium text-gray-500';
    @endphp

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-6 py-5">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Audit log</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">An append-only record of who did what. Entries cannot be edited or deleted.</p>
        </div>

        <form method="GET" action="{{ route('audit.index') }}" class="grid gap-3 px-6 pb-5 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2"><label for="q" class="{{ $label }}">Search the summary</label><input id="q" name="q" value="{{ request('q') }}" class="{{ $input }}"></div>
            <div>
                <label for="action" class="{{ $label }}">Action</label>
                <select id="action" name="action" class="{{ $input }}">
                    <option value="">Any</option>
                    @foreach ($actions as $a)<option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>@endforeach
                </select>
            </div>
            <div><label for="actor" class="{{ $label }}">Who</label><input id="actor" name="actor" value="{{ request('actor') }}" class="{{ $input }}"></div>
            <div><label for="bid" class="{{ $label }}">Bid reference</label><input id="bid" name="bid" value="{{ request('bid') }}" class="{{ $input }}"></div>
            <div class="grid grid-cols-2 gap-2">
                <div><label for="from" class="{{ $label }}">From</label><input id="from" type="date" name="from" value="{{ request('from') }}" class="{{ $input }}"></div>
                <div><label for="to" class="{{ $label }}">To</label><input id="to" type="date" name="to" value="{{ request('to') }}" class="{{ $input }}"></div>
            </div>
            <div class="flex items-end gap-3 lg:col-span-6">
                <button class="h-10 rounded-lg bg-brand-500 px-5 text-sm font-medium text-white hover:bg-brand-600">Filter</button>
                <a href="{{ route('audit.index') }}" class="text-sm text-gray-500 hover:text-gray-800">Clear</a>
                @if ($errors->any())<span class="text-sm text-red-600">{{ $errors->first() }}</span>@endif
            </div>
        </form>

        <div class="overflow-x-auto border-t border-gray-100 dark:border-gray-800">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        <th class="px-6 py-3 font-medium">When</th>
                        <th class="px-6 py-3 font-medium">Who</th>
                        <th class="px-6 py-3 font-medium">Action</th>
                        <th class="px-6 py-3 font-medium">What happened</th>
                        <th class="px-6 py-3 font-medium">IP</th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 dark:text-white/90">
                    @forelse ($logs as $log)
                        <tr class="border-b border-gray-100 align-top dark:border-gray-800">
                            <td class="whitespace-nowrap px-6 py-3">{{ $log->occurred_at->format('M j, Y g:i:s A T') }}</td>
                            <td class="px-6 py-3">{{ $log->actor_name ?? 'System' }}</td>
                            <td class="whitespace-nowrap px-6 py-3 font-mono text-xs">{{ $log->action }}</td>
                            <td class="px-6 py-3">
                                {{ $log->summary }}
                                @if ($log->properties)
                                    <details class="mt-1">
                                        <summary class="cursor-pointer text-xs text-brand-500">Details</summary>
                                        <pre class="mt-1 max-w-xl overflow-x-auto rounded bg-gray-50 p-2 text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-3 text-gray-500">{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">No entries match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
@endsection