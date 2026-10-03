@php
    $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400';
    $isDraft = ! $bid->exists || $bid->status === \App\Enums\BidStatus::Draft;
@endphp

@if ($isDraft)
    <div>
        <label for="reference_number" class="{{ $label }}">Reference number</label>
        <input id="reference_number" name="reference_number" value="{{ old('reference_number', $bid->reference_number) }}" placeholder="Leave blank to generate one" class="{{ $input }}">
        <x-input-error :messages="$errors->get('reference_number')" class="mt-2" />
    </div>
@endif

<div>
    <label for="title" class="{{ $label }}">Title</label>
    <input id="title" name="title" value="{{ old('title', $bid->title) }}" required class="{{ $input }}">
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div>
    <label for="department" class="{{ $label }}">Department</label>
    <input id="department" name="department" list="departments" value="{{ old('department', $bid->department) }}" class="{{ $input }}">
    <datalist id="departments">
        @foreach ($departments as $d)<option value="{{ $d }}">@endforeach
    </datalist>
    <x-input-error :messages="$errors->get('department')" class="mt-2" />
</div>

<div>
    <label for="closes_at" class="{{ $label }}">Responses due ({{ config('app.timezone') }})</label>
    <input id="closes_at" type="datetime-local" name="closes_at" value="{{ old('closes_at', $bid->closes_at?->format('Y-m-d\TH:i')) }}" class="{{ $input }}">
    <x-input-error :messages="$errors->get('closes_at')" class="mt-2" />
</div>

<div>
    <label for="description" class="{{ $label }}">Description</label>
    <textarea id="description" name="description" rows="10" required class="{{ str_replace('h-11', '', $input) }}">{{ old('description', $bid->description) }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

@can('assign', \App\Models\Bid::class)
    <div>
        <label for="assigned_to" class="{{ $label }}">Public contact</label>
        <select id="assigned_to" name="assigned_to" class="{{ $input }}">
            <option value="">Department default ({{ config('procurely.contact.name') }})</option>
            @foreach ($users as $u)
                <option value="{{ $u->id }}" @selected((string) old('assigned_to', $bid->assigned_to) === (string) $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('assigned_to')" class="mt-2" />
    </div>
@endcan

<button type="submit" class="rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white hover:bg-brand-600">Save</button>