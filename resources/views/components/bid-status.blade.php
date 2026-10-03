@props(['bid'])
@php
    $label = $bid->displayStatus();
    $classes = match ($label) {
        'Open' => 'bg-green-50 text-green-700',
        'Awarded' => 'bg-blue-50 text-blue-700',
        'Cancelled' => 'bg-red-50 text-red-700',
        'Draft' => 'bg-amber-50 text-amber-700',
        default => 'bg-gray-100 text-gray-700',
    };
@endphp
<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">{{ $label }}</span>