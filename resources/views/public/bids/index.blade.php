@extends('layouts.public')

@section('title', $heading)

@section('content')
    <div class="mx-auto max-w-6xl px-4 pt-10 sm:px-6">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">{{ $heading }}</h1>
        <p class="mt-2 text-gray-600">{{ $intro }}</p>

        @if ($mode === 'search')
            @php $field = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10'; @endphp
            <form method="GET" action="{{ route('bids.search') }}" class="mt-6 grid gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="sm:col-span-2">
                    <label for="q" class="mb-1 block text-sm font-medium text-gray-700">Keyword or reference number</label>
                    <input id="q" name="q" value="{{ request('q') }}" class="{{ $field }}">
                </div>
                <div>
                    <label for="department" class="mb-1 block text-sm font-medium text-gray-700">Department</label>
                    <select id="department" name="department" class="{{ $field }}">
                        <option value="">Any</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d }}" @selected(request('department') === $d)>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" class="{{ $field }}">
                        <option value="">Any</option>
                        @foreach (['open' => 'Open', 'closed' => 'Closed (all)', 'awarded' => 'Awarded', 'cancelled' => 'Cancelled'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="from" class="mb-1 block text-sm font-medium text-gray-700">Closes on or after</label>
                    <input id="from" type="date" name="from" value="{{ request('from') }}" class="{{ $field }}">
                </div>
                <div>
                    <label for="to" class="mb-1 block text-sm font-medium text-gray-700">Closes on or before</label>
                    <input id="to" type="date" name="to" value="{{ request('to') }}" class="{{ $field }}">
                </div>
                <div class="flex items-end gap-3 sm:col-span-2">
                    <button type="submit" class="h-11 rounded-lg bg-brand-500 px-6 text-sm font-medium text-white hover:bg-brand-600">Search</button>
                    <a href="{{ route('bids.search') }}" class="text-sm text-gray-600 hover:text-gray-900">Clear</a>
                </div>
                @if ($errors->any())
                    <p class="text-sm text-red-600 sm:col-span-2 lg:col-span-4">{{ $errors->first() }}</p>
                @endif
            </form>
        @endif

        <div class="mt-6">
            @include('public.bids._table', ['bids' => $bids])
        </div>

        <div class="mt-6">{{ $bids->links() }}</div>
    </div>
@endsection