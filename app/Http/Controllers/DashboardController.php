<?php

namespace App\Http\Controllers;

use App\Enums\BidStatus;
use App\Models\AuditLog;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        // Admins see every bid; staff see the bids assigned to them.
        $scoped = fn (Builder $q) => $isAdmin ? $q : $q->where('assigned_to', $user->id);

        $drafts = fn () => Bid::where('status', BidStatus::Draft)
            ->when(! $isAdmin, fn ($q) => $q->where('created_by', $user->id));
        $open = fn () => $scoped(Bid::open());
        $closingSoon = fn () => $scoped(Bid::open())->where('closes_at', '<=', now()->addDays(7));
        $awaiting = fn () => $scoped(
            Bid::where('status', BidStatus::Published)->where('closes_at', '<=', now())
        );

        return view('dashboard', [
            'isAdmin' => $isAdmin,
            'stats' => [
                'drafts' => $drafts()->count(),
                'open' => $open()->count(),
                'closing_soon' => $closingSoon()->count(),
                'awaiting' => $awaiting()->count(),
                'users' => $isAdmin ? User::where('is_active', true)->count() : null,
            ],
            'draftList' => $drafts()->with('creator')->latest('updated_at')->limit(8)->get(),
            'closingList' => $closingSoon()->withCount('responses')->orderBy('closes_at')->limit(8)->get(),
            'awaitingList' => $awaiting()->withCount('responses')->orderBy('closes_at')->limit(8)->get(),
            'unassigned' => $isAdmin ? Bid::open()->whereNull('assigned_to')->count() : 0,
            'activity' => $isAdmin
                ? AuditLog::orderByDesc('occurred_at')->orderByDesc('id')->limit(10)->get()
                : collect(),
        ]);
    }
}
