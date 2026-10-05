@extends('layouts.app')

@section('title', 'Edit '.$user->name)

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
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700" role="alert">{{ session('error') }}</div>
    @endif

    <div class="mb-4"><a href="{{ route('staff.index') }}" class="text-sm text-gray-500 hover:text-gray-800">&larr; Staff</a></div>

    <div class="max-w-2xl space-y-6">
        <div class="{{ $card }}">
            <h3 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90">
                {{ $user->name }}
                @unless ($user->is_active)<span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">Disabled</span>@endunless
            </h3>

            <form method="POST" action="{{ route('staff.update', $user) }}" class="space-y-4">
                @csrf @method('PUT')

                <div>
                    <label for="name" class="{{ $label }}">Name</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="{{ $input }}">
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <label for="email" class="{{ $label }}">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="{{ $input }}">
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="phone" class="{{ $label }}">Phone</label>
                        <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="{{ $input }}">
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>
                    <div>
                        <label for="job_title" class="{{ $label }}">Job title</label>
                        <input id="job_title" name="job_title" value="{{ old('job_title', $user->job_title) }}" class="{{ $input }}">
                        <x-input-error :messages="$errors->get('job_title')" class="mt-2" />
                    </div>
                </div>
                <div>
                    <label for="role" class="{{ $label }}">Role</label>
                    <select id="role" name="role" class="{{ $input }}">
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', $user->role->value) === $role->value)>{{ ucfirst($role->value) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </div>

                <p class="text-xs text-gray-500">Phone and job title appear publicly when this person is the assigned contact on a bid.</p>
                <button type="submit" class="{{ $btn }} bg-brand-500 hover:bg-brand-600">Save</button>
            </form>
        </div>

        <div class="{{ $card }} space-y-6">
            <h4 class="font-semibold text-gray-800 dark:text-white/90">Account actions</h4>

            @if ($openBids > 0)
                <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                    {{ $user->name }} is the public contact on {{ $openBids }} open {{ \Illuminate\Support\Str::plural('bid', $openBids) }}. If they are disabled or removed, those bids show the department contact instead. Reassign them first if that is not what you want.
                </p>
            @endif

            <form method="POST" action="{{ route('staff.reset-link', $user) }}">
                @csrf
                <button class="{{ $btn }} bg-gray-800 hover:bg-gray-900">Send password reset link</button>
                <p class="mt-2 text-sm text-gray-500">Emails {{ $user->email }} a link to choose a new password.</p>
            </form>

            @if ($user->is_active)
                <form method="POST" action="{{ route('staff.disable', $user) }}" onsubmit="return confirm('Disable this account? They will be signed out and unable to log in.')">
                    @csrf
                    <button class="{{ $btn }} bg-amber-600 hover:bg-amber-700">Disable account</button>
                    <p class="mt-2 text-sm text-gray-500">Temporary. Use for leave or a suspension. You can enable it again.</p>
                </form>
            @else
                <form method="POST" action="{{ route('staff.enable', $user) }}">
                    @csrf
                    <button class="{{ $btn }} bg-green-600 hover:bg-green-700">Enable account</button>
                </form>
            @endif

            <form method="POST" action="{{ route('staff.destroy', $user) }}" onsubmit="return confirm('Remove this person? They cannot sign in. You can restore them from the Former tab.')">
                @csrf @method('DELETE')
                <button class="{{ $btn }} bg-red-600 hover:bg-red-700">Remove (left the organization)</button>
                <p class="mt-2 text-sm text-gray-500">Their history stays. They move to the Former tab and can be restored.</p>
            </form>
        </div>
    </div>
@endsection