<?php

namespace App\Http\Controllers;

use App\Enums\ResponseMethod;
use App\Http\Requests\VendorResponseRequest;
use App\Mail\ResponseReceived;
use App\Models\Bid;
use App\Models\BidResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class VendorResponseController extends Controller
{
    public function create(string $reference): View|RedirectResponse
    {
        $bid = $this->visibleBid($reference);

        if (! $bid->isOpen()) {
            return $this->closedRedirect($bid);
        }

        return view('public.bids.respond', ['bid' => $bid]);
    }

    public function store(VendorResponseRequest $request, string $reference): RedirectResponse
    {
        $bid = $this->visibleBid($reference);

        if (! $bid->isOpen()) {
            return $this->closedRedirect($bid);
        }

        $response = DB::transaction(function () use ($request, $bid) {
            $response = new BidResponse(Arr::only($request->validated(), [
                'vendor_name', 'contact_name', 'contact_email', 'contact_phone', 'cover_note',
            ]));
            $response->bid_id = $bid->id;
            $response->method = ResponseMethod::Online;
            $response->receipt_code = BidResponse::generateReceiptCode();
            $response->submitted_at = now();
            $response->ip_address = $request->ip();
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

        $emailed = false;
        try {
            Mail::to($response->contact_email)->send(new ResponseReceived($response->load('bid.assignee', 'files')));
            $emailed = true;
        } catch (Throwable $e) {
            report($e);
        }

        return redirect()
            ->route('bids.respond.received', $bid->reference_number)
            ->with('receipt', $response->receiptLabel())
            ->with('emailed_to', $emailed ? $response->contact_email : null);
    }

    public function received(string $reference): View|RedirectResponse
    {
        $bid = $this->visibleBid($reference);

        if (! session('receipt')) {
            return redirect()->route('bids.show', $bid->reference_number);
        }

        return view('public.bids.received', ['bid' => $bid]);
    }

    private function visibleBid(string $reference): Bid
    {
        return Bid::visible()->where('reference_number', $reference)->with('assignee')->firstOrFail();
    }

    private function closedRedirect(Bid $bid): RedirectResponse
    {
        return redirect()
            ->route('bids.show', $bid->reference_number)
            ->with('error', 'This bid is no longer accepting online responses.');
    }
}
