<?php

namespace App\Policies;

use App\Enums\BidStatus;
use App\Models\Bid;
use App\Models\User;

class BidPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Bid $bid): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Bid $bid): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $bid->status === BidStatus::Draft && $bid->created_by === $user->id;
    }

    public function delete(User $user, Bid $bid): bool
    {
        return $bid->status === BidStatus::Draft && $this->update($user, $bid);
    }

    public function publish(User $user, Bid $bid): bool
    {
        return $user->isAdmin() && $bid->status === BidStatus::Draft;
    }

    public function cancel(User $user, Bid $bid): bool
    {
        return $user->isAdmin() && $bid->status === BidStatus::Published;
    }

    public function award(User $user, Bid $bid): bool
    {
        return $user->isAdmin()
            && $bid->status === BidStatus::Published
            && $bid->closes_at?->isPast();
    }

    public function assign(User $user): bool
    {
        return $user->isAdmin();
    }

    public function viewResponses(User $user, Bid $bid): bool
    {
        return $bid->status !== BidStatus::Draft
            && $bid->closes_at?->isPast()
            && ($user->isAdmin() || $bid->assigned_to === $user->id);
    }

    public function logResponse(User $user, Bid $bid): bool
    {
        return $bid->status === BidStatus::Published
            && ($user->isAdmin() || $bid->assigned_to === $user->id);
    }
}