<?php

namespace App\Http\Controllers;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\BidDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicBidController extends Controller
{
    public function home(): View
    {
        return view('public.home', [
            'openCount' => Bid::open()->count(),
            'closedCount' => Bid::closed()->count(),
            'latest' => Bid::open()->orderBy('closes_at')->limit(5)->get(),
        ]);
    }

    public function open(): View
    {
        return $this->list('open', 'Open bids', 'Opportunities that are currently accepting responses.', Bid::open()->orderBy('closes_at'));
    }

    public function closed(): View
    {
        return $this->list('closed', 'Closed bids', 'Past bids, including cancellations and awards.', Bid::closed()->orderByDesc('closes_at'));
    }

    public function search(Request $request): View
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['open', 'closed', 'awarded', 'cancelled'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Bid::visible();

        if (filled($data['q'] ?? null)) {
            $term = '%'.addcslashes($data['q'], '%_\\').'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('reference_number', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if (filled($data['department'] ?? null)) {
            $query->where('department', $data['department']);
        }

        $status = $data['status'] ?? null;
        if ($status === 'open') {
            $query->open();
        } elseif ($status === 'closed') {
            $query->closed();
        } elseif ($status === 'awarded') {
            $query->where('status', BidStatus::Awarded);
        } elseif ($status === 'cancelled') {
            $query->where('status', BidStatus::Cancelled);
        }

        if (filled($data['from'] ?? null)) {
            $query->whereDate('closes_at', '>=', $data['from']);
        }
        if (filled($data['to'] ?? null)) {
            $query->whereDate('closes_at', '<=', $data['to']);
        }

        return view('public.bids.index', [
            'mode' => 'search',
            'heading' => 'Search bids',
            'intro' => 'Find current and past bids by keyword, department, status or closing date.',
            'bids' => $query->orderByDesc('closes_at')->paginate(15)->withQueryString(),
            'departments' => Bid::visible()->whereNotNull('department')->distinct()->orderBy('department')->pluck('department'),
        ]);
    }

    public function show(string $reference): View
    {
        $bid = Bid::visible()
            ->where('reference_number', $reference)
            ->with(['documents', 'assignee'])
            ->firstOrFail();

        return view('public.bids.show', ['bid' => $bid]);
    }

    public function document(string $reference, BidDocument $document): StreamedResponse
    {
        $bid = Bid::visible()->where('reference_number', $reference)->firstOrFail();

        abort_unless($document->bid_id === $bid->id, 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    private function list(string $mode, string $heading, string $intro, Builder $query): View
    {
        return view('public.bids.index', [
            'mode' => $mode,
            'heading' => $heading,
            'intro' => $intro,
            'bids' => $query->paginate(15)->withQueryString(),
        ]);
    }
}
