@extends('layouts.app')

@section('title', 'Staff')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-500/15 dark:text-green-400" role="status">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-700" role="alert">{{ session('error') }}</div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-5">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Staff</h3>
            <a href="{{ route('staff.create') }}" class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">Add user</a>
        </div>

        <div class="flex flex-wrap items-center gap-2 px-6 pb-4 text-sm">
            @foreach (['all' => 'All', 'active' => 'Active', 'disabled' => 'Disabled', 'former' => 'Former'] as $key => $label)
                <a href="{{ route('staff.index', array_filter(['filter' => $key === 'all' ? null : $key, 'q' => request('q')])) }}"
                   @class([
                       'rounded-full px-3 py-1',
                       'bg-brand-500 text-white' => $filter === $key,
                       'border border-gray-200 text-gray-600 hover:text-gray-900 dark:border-gray-700 dark:text-gray-400' => $filter !== $key,
                   ])>{{ $label }} ({{ $counts[$key] }})</a>
            @endforeach

            <form method="GET" action="{{ route('staff.index') }}" class="ml-auto flex gap-2">
                @if ($filter !== 'all')<input type="hidden" name="filter" value="{{ $filter }}">@endif
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or email" aria-label="Search staff"
                       class="h-9 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white/90">
                <button class="h-9 rounded-lg border border-gray-300 px-3 text-gray-700 dark:border-gray-700 dark:text-gray-300">Search</button>
            </form>
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
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="text-gray-800 dark:text-white/90">
                    @forelse ($users as $user)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-6 py-3">
                                {{ $user->name }}
                                @if ($user->job_title)<span class="block text-xs text-gray-500">{{ $user->job_title }}</span>@endif
                            </td>
                            <td class="px-6 py-3">{{ $user->email }}</td>
                            <td class="px-6 py-3">{{ ucfirst($user->role->value) }}</td>
                            <td class="px-6 py-3">
                                @if ($user->trashed()) Former
                                @elseif ($user->is_active) Active
                                @else Disabled
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-3">{{ $user->created_at->format('M j, Y') }}</td>
                            <td class="px-6 py-3">{{ $user->creator?->name ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right">
                                @if ($user->trashed())
                                    <form method="POST" action="{{ route('staff.restore', $user) }}" class="inline">
                                        @csrf
                                        <button class="text-brand-500 hover:text-brand-600">Restore</button>
                                    </form>
                                @else
                                    <a href="{{ route('staff.edit', $user) }}" class="text-brand-500 hover:text-brand-600">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-10 text-center text-gray-500">No users match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
