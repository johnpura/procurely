<?php

namespace App\Models;

use App\Enums\ResponseMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BidResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_name', 'contact_name', 'contact_email', 'contact_phone', 'cover_note', 'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'method' => ResponseMethod::class,
            'submitted_at' => 'datetime',
        ];
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(BidResponseFile::class);
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by')->withTrashed();
    }

    public function isLate(): bool
    {
        return $this->bid->closes_at && $this->submitted_at->gt($this->bid->closes_at);
    }

    /** Easy to read aloud: 8 characters without look-alikes, shown as ABCD-EFGH. */
    public static function generateReceiptCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::where('receipt_code', $code)->exists());

        return $code;
    }

    public function receiptLabel(): string
    {
        return substr($this->receipt_code, 0, 4).'-'.substr($this->receipt_code, 4);
    }
}
