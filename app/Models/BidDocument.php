<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidDocument extends Model
{
    protected $fillable = ['bid_id', 'name', 'path', 'original_name', 'size'];

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }
}