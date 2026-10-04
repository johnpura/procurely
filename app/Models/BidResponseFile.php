<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidResponseFile extends Model
{
    protected $fillable = ['bid_response_id', 'original_name', 'path', 'size'];

    public function response(): BelongsTo
    {
        return $this->belongsTo(BidResponse::class, 'bid_response_id');
    }
}
