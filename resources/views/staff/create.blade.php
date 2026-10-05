@extends('layouts.app')

@section('title', 'Add user')

@section('content')
    @php
        $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
        $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400';
    @endphp

    <div class="max-w-xl rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="mb-5 text-lg font-semibold text-gray-800 dark:text-white/90">Add user</h3>

        <form method="POST" action="{{ route('staff.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="{{ $label }}">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" required autofocus class="{{ $input }}">
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <label for="email" class="{{ $label }}">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="{{ $input }}">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="phone" class="{{ $label }}">Phone (optional)</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}" class="{{ $input }}">
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>
                <div>
                    <label for="job_title" class="{{ $label }}">Job title (optional)</label>
                    <input id="job_title" name="job_title" value="{{ old('job_title') }}" class="{{ $input }}">
                    <x-input-error :messages="$errors->get('job_title')" class="mt-2" />
                </div>
            </div>

            <div>
                <label for="role" class="{{ $label }}">Role</label>
                <select id="role" name="role" class="{{ $input }}">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role', 'staff') === $role->value)>
                            {{ ucfirst($role->value) }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="{{ $label }}">Password</label>
                <input id="password" type="password" name="password" required class="{{ $input }}">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="password_confirmation" class="{{ $label }}">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required class="{{ $input }}">
            </div>

            <button type="submit"
                    class="rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white hover:bg-brand-600">
                Create user
            </button>
        </form>
    </div>
@endsection