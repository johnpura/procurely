@extends('layouts.public')

@section('title', 'Respond to '.$bid->reference_number)

@section('content')
    @push('scripts')
        @if (config('services.turnstile.site_key'))
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endif
    @endpush
    @php
        $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10';
        $label = 'mb-1 block text-sm font-medium text-gray-700';
    @endphp
    <div class="mx-auto max-w-2xl px-4 pt-10 sm:px-6">
        <a href="{{ route('bids.show', $bid->reference_number) }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; {{ $bid->reference_number }}</a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-gray-900">Submit a response</h1>
        <p class="mt-1 text-gray-600">{{ $bid->title }}</p>

        <div class="mt-4 rounded-lg bg-amber-50 p-4 text-sm text-amber-900">
            Responses are due <strong>{{ $bid->closes_at->format('M j, Y g:i A T') }}</strong>, measured by this site's clock when your upload finishes. Start early for large files. A response <strong>cannot be changed</strong> after it is submitted.
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-800" role="alert">
                <p class="font-medium">Please fix the following:</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('bids.respond.store', $bid->reference_number) }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf

            <div class="hidden" aria-hidden="true">
                <label for="website">Leave this empty</label>
                <input id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label for="vendor_name" class="{{ $label }}">Company name</label>
                <input id="vendor_name" name="vendor_name" value="{{ old('vendor_name') }}" required class="{{ $input }}" autocomplete="organization">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="contact_name" class="{{ $label }}">Contact name</label>
                    <input id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required class="{{ $input }}" autocomplete="name">
                </div>
                <div>
                    <label for="contact_phone" class="{{ $label }}">Phone (optional)</label>
                    <input id="contact_phone" name="contact_phone" value="{{ old('contact_phone') }}" class="{{ $input }}" autocomplete="tel">
                </div>
            </div>
            <div>
                <label for="contact_email" class="{{ $label }}">Email (your receipt is sent here)</label>
                <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email') }}" required class="{{ $input }}" autocomplete="email">
            </div>
            <div>
                <label for="cover_note" class="{{ $label }}">Cover note (optional)</label>
                <textarea id="cover_note" name="cover_note" rows="4" class="w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10">{{ old('cover_note') }}</textarea>
            </div>
            <div>
                <label for="files" class="{{ $label }}">PDF files (1 to 5 files, up to 20 MB each)</label>
                <input id="files" type="file" name="files[]" accept="application/pdf" multiple required class="block w-full text-sm text-gray-700">
            </div>
            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="checkbox" name="acknowledge" value="1" required class="mt-0.5 rounded border-gray-300 text-brand-500">
                <span>I understand that this response cannot be changed or withdrawn after it is submitted.</span>
            </label>
            @if (config('services.turnstile.site_key'))
                <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
            @endif
            <button type="submit" class="rounded-lg bg-brand-500 px-6 py-3 text-sm font-medium text-white hover:bg-brand-600">Submit response</button>
        </form>
    </div>
@endsection