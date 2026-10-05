<?php

namespace App\Models;

use App\Enums\BidStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bid extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number', 'title', 'department', 'description', 'closes_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BidStatus::class,
            'published_at' => 'datetime',
            'closes_at' => 'datetime',
            'awarded_at' => 'date',
            'award_amount' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference_number';
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BidDocument::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(BidResponse::class);
    }

    public function contact(): array
    {
        $default = config('procurely.contact');

        $user = $this->assignee;
        $usable = $user && $user->is_active;

        /** Public contact: the assigned user, else the department default. */
        return [
            'name' => $this->assignee?->name ?? $default['name'],
            'email' => $this->assignee?->email ?? $default['email'],
            'phone' => $this->assignee?->phone ?: $default['phone'],
            'title' => $usable ? $user->job_title : null,
        ];
    }

    /** Anything the public may see: everything except drafts. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', '!=', BidStatus::Draft);
    }

    /** Published and still accepting responses. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', BidStatus::Published)
            ->where('closes_at', '>', now());
    }

    /** Visible bids that are no longer accepting responses. */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->visible()->where(function (Builder $q) {
            $q->where('status', '!=', BidStatus::Published)
                ->orWhere('closes_at', '<=', now());
        });
    }

    public function isOpen(): bool
    {
        return $this->status === BidStatus::Published && $this->closes_at?->isFuture();
    }

    /** What the public sees: Open, Closed, Awarded, Cancelled or Draft. */
    public function displayStatus(): string
    {
        return match (true) {
            $this->status === BidStatus::Draft => 'Draft',
            $this->status === BidStatus::Awarded => 'Awarded',
            $this->status === BidStatus::Cancelled => 'Cancelled',
            $this->isOpen() => 'Open',
            default => 'Closed',
        };
    }

    public static function nextReferenceNumber(): string
    {
        $year = now()->year;

        $last = static::where('reference_number', 'like', "BID-{$year}-%")
            ->orderByDesc('reference_number')
            ->value('reference_number');

        return sprintf('BID-%d-%04d', $year, $last ? ((int) substr($last, -4)) + 1 : 1);
    }

    public function awardedResponse(): BelongsTo
    {
        return $this->belongsTo(BidResponse::class, 'awarded_response_id');
    }

}
