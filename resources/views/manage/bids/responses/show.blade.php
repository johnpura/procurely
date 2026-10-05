@extends('layouts.app')

@section('title', 'Response '.$response->receiptLabel())

@section('content')
    <div class="mb-4">
        <a href="{{ route('manage.bids.responses.index', $bid) }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; All responses to {{ $bid->reference_number }}</a>
    </div>

    <div class="max-w-3xl space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-wrap items-center gap-3">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $response->vendor_name }}</h3>
                @if ($response->isLate())
                    <span class="rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700" role="alert">Late</span>
                @endif
            </div>

            <dl class="mt-4 grid gap-x-8 gap-y-3 text-sm text-gray-800 dark:text-white/90 sm:grid-cols-2">
                <div><dt class="text-gray-500">Receipt code</dt><dd class="font-mono">{{ $response->receiptLabel() }}</dd></div>
                <div><dt class="text-gray-500">Method</dt><dd>{{ $response->method->label() }}</dd></div>
                <div><dt class="text-gray-500">Received</dt><dd>{{ $response->submitted_at->format('M j, Y g:i A T') }}</dd></div>
                <div><dt class="text-gray-500">Bid closed</dt><dd>{{ $bid->closes_at->format('M j, Y g:i A T') }}</dd></div>
                <div><dt class="text-gray-500">Contact</dt><dd>{{ $response->contact_name ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Email</dt><dd>{{ $response->contact_email ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Phone</dt><dd>{{ $response->contact_phone ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Logged by</dt><dd>{{ $response->loggedBy?->name ?? 'Submitted online' }}</dd></div>
            </dl>

            @if ($response->cover_note)
                <h4 class="mt-6 text-sm font-semibold text-gray-800 dark:text-white/90">Cover note</h4>
                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{!! nl2br(e($response->cover_note)) !!}</p>
            @endif

            @if ($response->internal_notes)
                <h4 class="mt-6 text-sm font-semibold text-gray-800 dark:text-white/90">Internal notes</h4>
                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{!! nl2br(e($response->internal_notes)) !!}</p>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <h4 class="mb-3 font-semibold text-gray-800 dark:text-white/90">Files</h4>
            @forelse ($response->files as $file)
                <a href="{{ route('manage.bids.responses.files.download', [$bid, $response, $file]) }}"
                   class="flex items-center justify-between gap-3 border-b border-gray-100 py-2 text-sm hover:text-brand-500 dark:border-gray-800">
                    <span class="min-w-0 truncate">{{ $file->original_name }}</span>
                    <span class="shrink-0 text-gray-500">{{ number_format($file->size / 1024) }} KB</span>
                </a>
            @empty
                <p class="text-sm text-gray-500">No files attached.</p>
            @endforelse
        </div>
    </div>
@endsection