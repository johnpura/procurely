<p>We received your response to <strong>{{ $response->bid->reference_number }} - {{ $response->bid->title }}</strong>.</p>

<p>
    Receipt code: <strong>{{ $response->receiptLabel() }}</strong><br>
    Received: {{ $response->submitted_at->format('M j, Y g:i A T') }}<br>
    Files: {{ $response->files->count() }}
</p>

<p>Please keep this message as your receipt. Responses cannot be changed after they are submitted. If you have a question, contact {{ $response->bid->contact()['name'] }} at {{ $response->bid->contact()['email'] }}.</p>

<p>{{ config('procurely.organization') }} &middot; {{ config('procurely.department') }}</p>