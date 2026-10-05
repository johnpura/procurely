<div class="overflow-x-auto rounded-xl border border-gray-200">
    <table class="min-w-full text-left text-sm">
        <caption class="sr-only">Bids with reference number, title, department, closing time and status</caption>
        <thead class="border-b border-gray-200 bg-gray-50 text-gray-500">
            <tr>
                <th scope="col" class="px-4 py-3 font-medium">Reference</th>
                <th scope="col" class="px-4 py-3 font-medium">Title</th>
                <th scope="col" class="px-4 py-3 font-medium">Department</th>
                <th scope="col" class="px-4 py-3 font-medium">Closes</th>
                <th scope="col" class="px-4 py-3 font-medium">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bids as $bid)
                <tr class="border-b border-gray-100 last:border-0">
                    <td class="whitespace-nowrap px-4 py-3">
                        <a href="{{ route('bids.show', $bid->reference_number) }}" class="font-medium text-brand-500 hover:text-brand-600">{{ $bid->reference_number }}</a>
                    </td>
                    <td class="px-4 py-3">
                        {{ $bid->title }}
                        @if ($bid->awarded_to)
                            <span class="mt-0.5 block text-xs text-gray-500">Awarded to {{ $bid->awarded_to }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $bid->department ?? '-' }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $bid->closes_at?->format('M j, Y g:i A T') ?? '-' }}</td>
                    <td class="px-4 py-3"><x-bid-status :bid="$bid" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No bids found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>