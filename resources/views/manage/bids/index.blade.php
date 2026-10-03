@extends('layouts.app')

@section('title', 'Bids')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-500/15 dark:text-green-400">{{ session('status') }}</div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-5">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Bids</h3>
            <a href="{{ route('manage.bids.create') }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">New bid</a>
        </div>

        <div class="flex flex-wrap items-center gap-2 px-6 pb-4 text-sm">
            @foreach (['all' => 'All', 'draft' => 'Drafts', 'open' => 'Open', 'closed' => 'Closed', 'awarded' => 'Awarded', 'cancelled' => 'Cancelled'] as $key => $label)
                <a href="{{ route('manage.bids.index', array_filter(['status' => $key === 'all' ? null : $key, 'mine' => request('mine')])) }}"
                   @class([
                       'rounded-full px-3 py-1',
                       'bg-brand-500 text-white' => $status === $key,
                       'border border-gray-200 text-gray-600 hover:text-gray-900 dark:border-gray-700 dark:text-gray-400' => $status !== $key,
                   ])>{{ $label }}</a>
            @endforeach
            <a href="{{ route('manage.bids.index', array_filter(['status' => $status === 'all' ? null : $status, 'mine' => request('mine') ? null : 1])) }}"
               class="ml-auto text-gray-600 hover:text-gray-900 dark:text-gray-400">{{ request('mine') ? 'Show everyone\'s bids' : 'Only my bids' }}</a>
        </div>

        <div class="overflow-x-auto border-t border-gray-100 dark:border-gray-800">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        <th class="px-6 py-3 font-medium">Reference</th>
                        <th class="px-6 py-3 font-medium">Title</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">Closes</th>
                        <th class="px-6 py-3 font-medium">Contact</th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 dark:text-white/90">
                    @forelse ($bids as $bid)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="whitespace-nowrap px-6 py-3">
                                <a href="{{ route('manage.bids.edit', $bid) }}" class="font-medium text-brand-500 hover:text-brand-600">{{ $bid->reference_number }}</a>
                            </td>
                            <td class="px-6 py-3">{{ $bid->title }}</td>
                            <td class="px-6 py-3"><x-bid-status :bid="$bid" /></td>
                            <td class="whitespace-nowrap px-6 py-3">{{ $bid->closes_at?->format('M j, Y g:i A') ?? '-' }}</td>
                            <td class="px-6 py-3">{{ $bid->assignee?->name ?? 'Department default' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">No bids match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $bids->links() }}</div>
@endsection