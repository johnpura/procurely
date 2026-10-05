@extends('layouts.app')

@section('title', $bid->reference_number)

@section('content')
    @php
        $card = 'rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]';
        $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
        $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400';
        $btn = 'rounded-lg px-4 py-2.5 text-sm font-medium text-white';
    @endphp

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-500/15 dark:text-green-400" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->has('publish'))
        <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700" role="alert">{{ $errors->first('publish') }}</div>
    @endif

    <div class="max-w-3xl space-y-6">
        <div class="flex flex-wrap items-center gap-3">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $bid->reference_number }}</h3>
            <x-bid-status :bid="$bid" />
            @if (auth()->user()->isAdmin())
                <a href="{{ route('audit.index', ['bid' => $bid->reference_number]) }}" class="text-sm text-brand-500 hover:text-brand-600">History</a>
            @endif
            <a href="{{ route('manage.bids.preview', $bid) }}" class="ml-auto text-sm text-brand-500 hover:text-brand-600">Preview public page</a>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Created {{ $bid->created_at->format('M j, Y') }} by {{ $bid->creator?->name ?? 'unknown' }}
        </p>

        <div class="{{ $card }}">
            @can('update', $bid)
                <form method="POST" action="{{ route('manage.bids.update', $bid) }}" class="space-y-4">
                    @csrf @method('PUT')
                    @include('manage.bids.partials.form')
                </form>
            @else
                <dl class="space-y-2 text-sm text-gray-800 dark:text-white/90">
                    <div><dt class="text-gray-500">Title</dt><dd>{{ $bid->title }}</dd></div>
                    <div><dt class="text-gray-500">Department</dt><dd>{{ $bid->department ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Responses due</dt><dd>{{ $bid->closes_at?->format('M j, Y g:i A T') ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Contact</dt><dd>{{ $bid->contact()['name'] }}</dd></div>
                </dl>
                <p class="mt-4 text-sm text-gray-500">You can edit only your own drafts.</p>
            @endcan
        </div>

        <div class="{{ $card }}">
            <h4 class="mb-3 font-semibold text-gray-800 dark:text-white/90">Documents</h4>
            @forelse ($bid->documents as $doc)
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 py-2 text-sm dark:border-gray-800">
                    <span class="min-w-0 truncate text-gray-800 dark:text-white/90">{{ $doc->name }} <span class="text-gray-500">({{ number_format($doc->size / 1024) }} KB)</span></span>
                    @can('update', $bid)
                        <form method="POST" action="{{ route('manage.bids.documents.destroy', [$bid, $doc]) }}" onsubmit="return confirm('Remove this document?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:text-red-700">Remove</button>
                        </form>
                    @endcan
                </div>
            @empty
                <p class="text-sm text-gray-500">No documents yet.</p>
            @endforelse

            @can('update', $bid)
                <form method="POST" action="{{ route('manage.bids.documents.store', $bid) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label for="doc_name" class="{{ $label }}">Display name (optional)</label>
                        <input id="doc_name" name="name" class="{{ $input }}">
                    </div>
                    <div>
                        <label for="doc_file" class="{{ $label }}">PDF file (max 20 MB)</label>
                        <input id="doc_file" type="file" name="file" accept="application/pdf" required class="block w-full text-sm text-gray-700 dark:text-gray-400">
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="{{ $btn }} bg-gray-800 hover:bg-gray-900">Upload</button>
                    </div>
                </form>
            @endcan
        </div>

        @canany(['viewResponses', 'logResponse'], $bid)
            @php $responseCount = $bid->responses()->count(); @endphp
            <div class="{{ $card }}">
                <h4 class="mb-2 font-semibold text-gray-800 dark:text-white/90">Responses</h4>

                @can('viewResponses', $bid)
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        {{ $responseCount }} received.
                        <a href="{{ route('manage.bids.responses.index', $bid) }}" class="text-brand-500 hover:text-brand-600">Open responses</a>
                    </p>
                @else
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        {{ $responseCount }} received so far. Details stay sealed until {{ $bid->closes_at?->format('M j, Y g:i A T') }}.
                    </p>
                @endcan

                @can('logResponse', $bid)
                    <a href="{{ route('manage.bids.responses.create', $bid) }}" class="mt-3 inline-block text-sm text-brand-500 hover:text-brand-600">Log a response received by email, mail or in person</a>
                @endcan
            </div>
        @endcanany

        @canany(['publish', 'cancel', 'award', 'delete'], $bid)
            <div class="{{ $card }} space-y-6">
                <h4 class="font-semibold text-gray-800 dark:text-white/90">Actions</h4>

                @can('publish', $bid)
                    <form method="POST" action="{{ route('manage.bids.publish', $bid) }}">
                        @csrf
                        <button class="{{ $btn }} bg-brand-500 hover:bg-brand-600">Publish</button>
                        <p class="mt-2 text-sm text-gray-500">Makes the bid and its documents public. A future closing time is required.</p>
                    </form>
                @endcan

                @can('award', $bid)
                    @php
                        $responders = $bid->responses()->orderBy('vendor_name')->get()
                            ->each(fn ($r) => $r->setRelation('bid', $bid))
                            ->reject(fn ($r) => $r->isLate());
                    @endphp
                    <form method="POST" action="{{ route('manage.bids.award', $bid) }}" class="grid gap-3 sm:grid-cols-3"
                        x-data="{ other: {{ $responders->isEmpty() || old('awarded_to') ? 'true' : 'false' }} }">
                        @csrf
                        <div class="sm:col-span-3"><p class="text-sm text-gray-500">The bid has closed. Record the award.</p></div>

                        <div class="sm:col-span-3">
                            <label for="awarded_response_id" class="{{ $label }}">Awarded to</label>
                            <select id="awarded_response_id" name="awarded_response_id" class="{{ $input }}"
                                    x-on:change="other = $event.target.value === ''">
                                @foreach ($responders as $r)
                                    <option value="{{ $r->id }}" @selected((string) old('awarded_response_id') === (string) $r->id)>
                                        {{ $r->vendor_name }} ({{ $r->receiptLabel() }})
                                    </option>
                                @endforeach
                                <option value="" @selected($responders->isEmpty() || old('awarded_to'))>Other vendor (type a name)</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">On-time responses only. Late responses cannot be awarded.</p>
                            <x-input-error :messages="$errors->get('awarded_response_id')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-3" x-show="other" x-cloak>
                            <label for="awarded_to" class="{{ $label }}">Vendor name</label>
                            <input id="awarded_to" name="awarded_to" value="{{ old('awarded_to') }}" x-bind:disabled="! other" class="{{ $input }}">
                            <x-input-error :messages="$errors->get('awarded_to')" class="mt-2" />
                        </div>

                        <div>
                            <label for="award_amount" class="{{ $label }}">Amount</label>
                            <input id="award_amount" name="award_amount" inputmode="decimal" value="{{ old('award_amount') }}" class="{{ $input }}">
                            <x-input-error :messages="$errors->get('award_amount')" class="mt-2" />
                        </div>
                        <div>
                            <label for="awarded_at" class="{{ $label }}">Award date</label>
                            <input id="awarded_at" type="date" name="awarded_at" value="{{ old('awarded_at', now()->toDateString()) }}" required class="{{ $input }}">
                            <x-input-error :messages="$errors->get('awarded_at')" class="mt-2" />
                        </div>
                        <div class="sm:col-span-3"><button class="{{ $btn }} bg-brand-500 hover:bg-brand-600">Record award</button></div>
                    </form>
                @endcan

                @can('cancel', $bid)
                    <form method="POST" action="{{ route('manage.bids.cancel', $bid) }}" onsubmit="return confirm('Cancel this bid? Vendors will see it as cancelled.')">
                        @csrf
                        <button class="{{ $btn }} bg-red-600 hover:bg-red-700">Cancel bid</button>
                    </form>
                @endcan

                @can('delete', $bid)
                    <form method="POST" action="{{ route('manage.bids.destroy', $bid) }}" onsubmit="return confirm('Delete this draft permanently?')">
                        @csrf @method('DELETE')
                        <button class="{{ $btn }} bg-red-600 hover:bg-red-700">Delete draft</button>
                    </form>
                @endcan
            </div>
        @endcanany
    </div>
@endsection