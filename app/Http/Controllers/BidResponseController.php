<?php

namespace App\Http\Controllers;

use App\Enums\ResponseMethod;
use App\Models\Bid;
use App\Models\BidResponse;
use App\Models\BidResponseFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BidResponseController extends Controller
{
    public function index(Bid $bid): View
    {
        Gate::authorize('viewResponses', $bid);

        $responses = $bid->responses()->withCount('files')->orderBy('submitted_at')->get();
        $responses->each->setRelation('bid', $bid);

        return view('manage.bids.responses.index', ['bid' => $bid, 'responses' => $responses]);
    }

    public function show(Bid $bid, BidResponse $response): View
    {
        Gate::authorize('viewResponses', $bid);
        abort_unless($response->bid_id === $bid->id, 404);

        $response->load(['files', 'loggedBy'])->setRelation('bid', $bid);

        return view('manage.bids.responses.show', ['bid' => $bid, 'response' => $response]);
    }

    public function file(Bid $bid, BidResponse $response, BidResponseFile $file): StreamedResponse
    {
        Gate::authorize('viewResponses', $bid);
        abort_unless($response->bid_id === $bid->id && $file->bid_response_id === $response->id, 404);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    public function create(Bid $bid): View
    {
        Gate::authorize('logResponse', $bid);

        return view('manage.bids.responses.create', ['bid' => $bid]);
    }

    public function store(Request $request, Bid $bid): RedirectResponse
    {
        Gate::authorize('logResponse', $bid);

        $data = $request->validate([
            'method' => ['required', Rule::in(['email', 'mail', 'in_person'])],
            'vendor_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'received_at' => ['required', 'date', 'before_or_equal:now'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['file', 'mimes:pdf', 'max:20480'],
        ]);

        $response = DB::transaction(function () use ($request, $bid, $data) {
            $response = new BidResponse(Arr::only($data, [
                'vendor_name', 'contact_name', 'contact_email', 'contact_phone', 'internal_notes',
            ]));
            $response->bid_id = $bid->id;
            $response->method = ResponseMethod::from($data['method']);
            $response->receipt_code = BidResponse::generateReceiptCode();
            $response->submitted_at = $data['received_at'];
            $response->logged_by = $request->user()->id;
            $response->save();

            foreach ($request->file('files', []) as $file) {
                $response->files()->create([
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $file->store("responses/{$bid->id}/{$response->id}", 'local'),
                    'size' => $file->getSize(),
                ]);
            }

            return $response;
        });

        return redirect()
            ->route('manage.bids.edit', $bid)
            ->with('status', "Response logged. Receipt code {$response->receiptLabel()}.");
    }
}