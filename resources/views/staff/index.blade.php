@extends('layouts.app')

@section('title', 'Staff')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-500/15 dark:text-green-400">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between px-6 py-5">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Staff</h3>
            <a href="{{ route('staff.create') }}"
               class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                Add user
            </a>
        </div>

        <div class="overflow-x-auto border-t border-gray-100 dark:border-gray-800">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                        <th class="px-6 py-3 font-medium">Name</th>
                        <th class="px-6 py-3 font-medium">Email</th>
                        <th class="px-6 py-3 font-medium">Role</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">Created</th>
                        <th class="px-6 py-3 font-medium">Created by</th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 dark:text-white/90">
                    @foreach ($users as $user)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-6 py-3">{{ $user->name }}</td>
                            <td class="px-6 py-3">{{ $user->email }}</td>
                            <td class="px-6 py-3">{{ ucfirst($user->role->value) }}</td>
                            <td class="px-6 py-3">
                                @if ($user->trashed())
                                    Former
                                @elseif ($user->is_active)
                                    Active
                                @else
                                    Disabled
                                @endif
                            </td>
                            <td class="px-6 py-3">{{ $user->created_at->format('M j, Y') }}</td>
                            <td class="px-6 py-3">{{ $user->creator?->name ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection