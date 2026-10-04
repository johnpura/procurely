@extends('layouts.app')

@section('title', 'Log a response')

@section('content')
    @php
        $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
        $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400';
    @endphp
    <div class="mb-4">
        <a href="{{ route('manage.bids.edit', $bid) }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; {{ $bid->reference_number }}</a>
    </div>

    <div class="max-w-2xl rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Log a response</h3>
        <p class="mb-5 mt-1 text-sm text-gray-500 dark:text-gray-400">
            Record a response that arrived by email, mail or in person. Enter the date and time it was actually received ({{ config('app.timezone') }}). Responses after {{ $bid->closes_at->format('M j, Y g:i A') }} are marked late.
        </p>

        <form method="POST" action="{{ route('manage.bids.responses.store', $bid) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="method" class="{{ $label }}">How was it received?</label>
                    <select id="method" name="method" class="{{ $input }}">
                        @foreach (['email' => 'Email', 'mail' => 'Mail', 'in_person' => 'In person'] as $value => $text)
                            <option value="{{ $value }}" @selected(old('method') === $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('method')" class="mt-2" />
                </div>
                <div>
                    <label for="received_at" class="{{ $label }}">Received at</label>
                    <input id="received_at" type="datetime-local" name="received_at" value="{{ old('received_at', now()->format('Y-m-d\TH:i')) }}" required class="{{ $input }}">
                    <x-input-error :messages="$errors->get('received_at')" class="mt-2" />
                </div>
            </div>

            <div>
                <label for="vendor_name" class="{{ $label }}">Company name</label>
                <input id="vendor_name" name="vendor_name" value="{{ old('vendor_name') }}" required class="{{ $input }}">
                <x-input-error :messages="$errors->get('vendor_name')" class="mt-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="contact_name" class="{{ $label }}">Contact name</label>
                    <input id="contact_name" name="contact_name" value="{{ old('contact_name') }}" class="{{ $input }}">
                </div>
                <div>
                    <label for="contact_email" class="{{ $label }}">Email</label>
                    <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email') }}" class="{{ $input }}">
                    <x-input-error :messages="$errors->get('contact_email')" class="mt-2" />
                </div>
                <div>
                    <label for="contact_phone" class="{{ $label }}">Phone</label>
                    <input id="contact_phone" name="contact_phone" value="{{ old('contact_phone') }}" class="{{ $input }}">
                </div>
            </div>

            <div>
                <label for="internal_notes" class="{{ $label }}">Internal notes (optional)</label>
                <textarea id="internal_notes" name="internal_notes" rows="3" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('internal_notes') }}</textarea>
            </div>

            <div>
                <label for="files" class="{{ $label }}">Scanned or attached PDFs (optional, up to 5)</label>
                <input id="files" type="file" name="files[]" accept="application/pdf" multiple class="block w-full text-sm text-gray-700 dark:text-gray-400">
                <x-input-error :messages="$errors->get('files')" class="mt-2" />
                <x-input-error :messages="$errors->get('files.0')" class="mt-2" />
            </div>

            <button type="submit" class="rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white hover:bg-brand-600">Log response</button>
        </form>
    </div>
@endsection