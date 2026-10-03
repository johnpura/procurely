<?php

namespace App\Http\Controllers;

use App\Enums\BidStatus;
use App\Http\Requests\BidRequest;
use App\Models\Bid;
use App\Models\BidDocument;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BidManageController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Bid::class);

        $status = $request->query('status', 'all');
        $query = Bid::with('assignee')->latest('updated_at');

        match ($status) {
            'draft' => $query->where('status', BidStatus::Draft),
            'open' => $query->open(),
            'closed' => $query->closed(),
            'awarded' => $query->where('status', BidStatus::Awarded),
            'cancelled' => $query->where('status', BidStatus::Cancelled),
            default => null,
        };

        if ($request->boolean('mine')) {
            $query->where('created_by', $request->user()->id);
        }

        return view('manage.bids.index', [
            'bids' => $query->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Bid::class);

        return view('manage.bids.create', $this->formData(new Bid));
    }

    public function store(BidRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $bid = new Bid(Arr::except($data, ['reference_number', 'assigned_to']));
        $bid->reference_number = filled($data['reference_number'] ?? null)
            ? $data['reference_number']
            : Bid::nextReferenceNumber();
        $bid->status = BidStatus::Draft;
        $bid->created_by = $request->user()->id;

        if (Gate::allows('assign', Bid::class)) {
            $bid->assigned_to = $data['assigned_to'] ?? null;
        }

        $bid->save();

        return redirect()->route('manage.bids.edit', $bid)->with('status', 'Draft created.');
    }

    public function edit(Bid $bid): View
    {
        Gate::authorize('view', $bid);

        return view('manage.bids.edit', $this->formData($bid->load(['documents', 'assignee', 'creator'])));
    }

    public function update(BidRequest $request, Bid $bid): RedirectResponse
    {
        $data = $request->validated();

        $bid->fill(Arr::except($data, ['reference_number', 'assigned_to']));

        if ($bid->status === BidStatus::Draft && filled($data['reference_number'] ?? null)) {
            $bid->reference_number = $data['reference_number'];
        }

        if (Gate::allows('assign', Bid::class)) {
            $bid->assigned_to = $data['assigned_to'] ?? null;
        }

        $bid->save();

        return redirect()->route('manage.bids.edit', $bid)->with('status', 'Bid saved.');
    }

    public function destroy(Bid $bid): RedirectResponse
    {
        Gate::authorize('delete', $bid);

        foreach ($bid->documents as $document) {
            Storage::disk('local')->delete($document->path);
        }

        $bid->delete();

        return redirect()->route('manage.bids.index')->with('status', 'Draft deleted.');
    }

    public function preview(Bid $bid): View
    {
        Gate::authorize('view', $bid);

        return view('public.bids.show', ['bid' => $bid->load(['documents', 'assignee']), 'preview' => true]);
    }

    public function publish(Bid $bid): RedirectResponse
    {
        Gate::authorize('publish', $bid);

        if (! $bid->closes_at || $bid->closes_at->isPast()) {
            return back()->withErrors(['publish' => 'Set a closing date and time in the future before publishing.']);
        }

        $bid->status = BidStatus::Published;
        $bid->published_at = now();
        $bid->save();

        return redirect()->route('manage.bids.edit', $bid)->with('status', 'Bid published.');
    }

    public function cancel(Bid $bid): RedirectResponse
    {
        Gate::authorize('cancel', $bid);

        $bid->status = BidStatus::Cancelled;
        $bid->save();

        return redirect()->route('manage.bids.edit', $bid)->with('status', 'Bid cancelled.');
    }

    public function award(Request $request, Bid $bid): RedirectResponse
    {
        Gate::authorize('award', $bid);

        $data = $request->validate([
            'awarded_to' => ['required', 'string', 'max:255'],
            'award_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'awarded_at' => ['required', 'date'],
        ]);

        $bid->status = BidStatus::Awarded;
        $bid->awarded_to = $data['awarded_to'];
        $bid->award_amount = $data['award_amount'] ?? null;
        $bid->awarded_at = $data['awarded_at'];
        $bid->save();

        return redirect()->route('manage.bids.edit', $bid)->with('status', 'Award recorded.');
    }

    public function storeDocument(Request $request, Bid $bid): RedirectResponse
    {
        Gate::authorize('update', $bid);

        $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $file = $request->file('file');

        $bid->documents()->create([
            'name' => $request->input('name') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'path' => $file->store("bids/{$bid->id}", 'local'),
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);

        return redirect()->route('manage.bids.edit', $bid)->with('status', 'Document added.');
    }

    public function destroyDocument(Bid $bid, BidDocument $document): RedirectResponse
    {
        Gate::authorize('update', $bid);
        abort_unless($document->bid_id === $bid->id, 404);

        Storage::disk('local')->delete($document->path);
        $document->delete();

        return redirect()->route('manage.bids.edit', $bid)->with('status', 'Document removed.');
    }

    private function formData(Bid $bid): array
    {
        return [
            'bid' => $bid,
            'users' => Gate::allows('assign', Bid::class)
                ? User::where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'departments' => Bid::whereNotNull('department')->distinct()->orderBy('department')->pluck('department'),
        ];
    }
}
