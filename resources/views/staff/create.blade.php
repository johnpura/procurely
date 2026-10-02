<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add user</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('staff.store') }}">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" :value="old('email')" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="role" value="Role" />
                        <select id="role" name="role" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected(old('role', 'staff') === $role->value)>
                                    {{ ucfirst($role->value) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="password" value="Password" />
                        <x-text-input id="password" type="password" name="password" class="block mt-1 w-full" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="password_confirmation" value="Confirm password" />
                        <x-text-input id="password_confirmation" type="password" name="password_confirmation" class="block mt-1 w-full" required />
                    </div>

                    <div class="mt-6">
                        <x-primary-button>Create user</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>