@extends('layouts.public')

@section('title', $bid->reference_number)

@section('content')
    @php $contact = $bid->contact(); @endphp
    <div class="mx-auto max-w-4xl px-4 pt-10 sm:px-6">
        @isset($preview)
            <div class="mb-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                Preview for staff. Draft bids are not public, and document links only work once the bid is published.
            </div>
        @endisset
        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif
        <a href="{{ route('bids.open') }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; All bids</a>

        <div class="mt-3 flex flex-wrap items-center gap-3">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ $bid->title }}</h1>
            <x-bid-status :bid="$bid" />
        </div>
        <p class="mt-1 text-gray-500">{{ $bid->reference_number }}</p>

        <dl class="mt-6 grid gap-x-8 gap-y-4 rounded-xl border border-gray-200 p-5 text-sm sm:grid-cols-2">
            <div><dt class="text-gray-500">Department</dt><dd class="font-medium">{{ $bid->department ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">Posted</dt><dd class="font-medium">{{ $bid->published_at?->format('M j, Y') ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">Responses due</dt><dd class="font-medium">{{ $bid->closes_at?->format('M j, Y g:i A T') ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">Contact</dt>
                <dd class="font-medium">{{ $contact['name'] }}<br>
                    <a href="mailto:{{ $contact['email'] }}" class="text-brand-500 hover:text-brand-600">{{ $contact['email'] }}</a><br>
                    {{ $contact['phone'] }}</dd></div>
        </dl>

        @if ($bid->status === \App\Enums\BidStatus::Awarded)
            <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-5 text-sm">
                <h2 class="font-semibold text-blue-900">Award</h2>
                <p class="mt-1 text-blue-900">
                    Awarded to <strong>{{ $bid->awarded_to }}</strong>
                    @if ($bid->award_amount) for ${{ number_format($bid->award_amount, 2) }}@endif
                    @if ($bid->awarded_at) on {{ $bid->awarded_at->format('M j, Y') }}@endif.
                </p>
            </div>
        @elseif ($bid->status === \App\Enums\BidStatus::Cancelled)
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-900">This bid was cancelled.</div>
        @endif

        <h2 class="mt-10 text-xl font-bold tracking-tight text-gray-900">Description</h2>
        <div class="mt-3 max-w-prose leading-relaxed text-gray-700">{!! nl2br(e($bid->description)) !!}</div>

        <h2 class="mt-10 text-xl font-bold tracking-tight text-gray-900">Documents</h2>
        @forelse ($bid->documents as $doc)
            <a href="{{ route('bids.documents.download', [$bid->reference_number, $doc]) }}"
               class="mt-3 flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-4 py-3 text-sm hover:border-brand-500">
                <span class="min-w-0 truncate font-medium text-gray-900">{{ $doc->name }}</span>
                <span class="shrink-0 text-gray-500">{{ number_format($doc->size / 1024) }} KB</span>
            </a>
        @empty
            <p class="mt-3 text-sm text-gray-500">No documents have been posted.</p>
        @endforelse

        @if ($bid->isOpen() && empty($preview))
            <a href="{{ route('bids.respond', $bid->reference_number) }}" class="mt-10 inline-block rounded-lg bg-brand-500 px-6 py-3 text-sm font-medium text-white hover:bg-brand-600">Submit a response online</a>
        @endif
        <h2 class="mt-10 text-xl font-bold tracking-tight text-gray-900">How to respond</h2>
        <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-gray-700">
            @if ($bid->isOpen() && empty($preview))
                <li>Online, using the "Submit a response online" button above</li>
            @endif
            <li>By email to <a href="mailto:{{ $contact['email'] }}" class="text-brand-500">{{ $contact['email'] }}</a></li>
            <li>By mail or in person: {{ config('procurely.contact.address') }}</li>
        </ul>
        <p class="mt-2 text-sm text-gray-500">Responses must be received by the closing time shown above.</p>
    </div>
@endsection