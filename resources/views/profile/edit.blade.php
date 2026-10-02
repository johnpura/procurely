@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    <div class="max-w-3xl space-y-6">
        @foreach (['update-profile-information-form', 'update-password-form', 'delete-user-form'] as $partial)
            <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="max-w-xl">
                    @include('profile.partials.' . $partial)
                </div>
            </div>
        @endforeach
    </div>
@endsection