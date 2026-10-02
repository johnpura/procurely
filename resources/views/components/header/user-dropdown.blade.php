@php
    $user = auth()->user();
    $initial = strtoupper(mb_substr($user->name, 0, 1));
@endphp

<div class="relative" x-data="{ isOpen: false }" @click.outside="isOpen = false">
    <button type="button" class="flex items-center text-gray-700 dark:text-gray-400" @click="isOpen = !isOpen">
        <span class="mr-3 flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-500 dark:bg-brand-500/15 dark:text-brand-400">
            {{ $initial }}
        </span>
        <span class="mr-1 block text-theme-sm font-medium">{{ $user->name }}</span>
        <svg :class="isOpen ? 'rotate-180' : ''" class="h-[18px] w-[18px] transition-transform duration-200"
             viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4.5 6.75L9 11.25L13.5 6.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>

    <div x-show="isOpen" x-transition style="display: none;"
         class="absolute right-0 z-50 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark">
        <div>
            <span class="block text-theme-sm font-medium text-gray-700 dark:text-gray-400">{{ $user->name }}</span>
            <span class="mt-0.5 block text-theme-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</span>
            <span class="mt-2 inline-block rounded-full bg-brand-50 px-2 py-0.5 text-theme-xs font-medium text-brand-500 dark:bg-brand-500/15 dark:text-brand-400">
                {{ ucfirst($user->role->value) }}
            </span>
        </div>

        <ul class="flex flex-col gap-1 border-b border-gray-200 pb-3 pt-4 dark:border-gray-800">
            <li>
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                    Edit profile
                </a>
            </li>
        </ul>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="mt-3 flex w-full items-center gap-3 rounded-lg px-3 py-2 text-theme-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                Sign out
            </button>
        </form>
    </div>
</div>