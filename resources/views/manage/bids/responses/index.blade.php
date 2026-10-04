@extends('layouts.app')

@section('title', 'Responses')

@section('content')
    @php
        $late = $responses->filter->isLate()->count();
    @endphp
    <div class="mb-4">
        <a href="{{ route('manage.bids.edit', $bid) }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; {{ $bid->reference_number }}</a>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-6 py-5">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Responses to {{ $bid->title }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $responses->count() }} received &middot; {{ $responses->count() - $late }} on time &middot; {{ $late }} late.
                Closed {{ $bid->closes_at->format('M j, Y g:i A T') }}.
            </p>
        </div>

        <div class="overflow-x-auto border-t border-gray-100 dark:border-gray-800">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        <th class="px-6 py-3 font-medium">Receipt</th>
                        <th class="px-6 py-3 font-medium">Vendor</th>
                        <th class="px-6 py-3 font-medium">Method</th>
                        <th class="px-6 py-3 font-medium">Received</th>
                        <th class="px-6 py-3 font-medium">Files</th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 dark:text-white/90">
                    @forelse ($responses as $r)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="whitespace-nowrap px-6 py-3">
                                <a href="{{ route('manage.bids.responses.show', [$bid, $r]) }}" class="font-mono font-medium text-brand-500 hover:text-brand-600">{{ $r->receiptLabel() }}</a>
                            </td>
                            <td class="px-6 py-3">{{ $r->vendor_name }}</td>
                            <td class="px-6 py-3">{{ $r->method->label() }}</td>
                            <td class="whitespace-nowrap px-6 py-3">
                                {{ $r->submitted_at->format('M j, Y g:i A') }}
                                @if ($r->isLate())
                                    <span class="ml-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Late</span>
                                @endif
                            </td>
                            <td class="px-6 py-3">{{ $r->files_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">No responses were received.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection