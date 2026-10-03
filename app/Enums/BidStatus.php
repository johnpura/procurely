<?php

namespace App\Enums;

enum BidStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Awarded = 'awarded';
    case Cancelled = 'cancelled';
}
