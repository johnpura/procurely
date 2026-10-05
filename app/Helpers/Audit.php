<?php

namespace App\Helpers;

use App\Models\AuditLog;
use App\Models\Bid;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Audit
{
    /**
     * Record an action. Pass $actor for non-staff actors (such as a vendor); the entry then has no user.
     */
    public static function record(
        string $action,
        string $summary,
        ?Model $subject = null,
        ?Bid $bid = null,
        array $properties = [],
        ?string $actor = null,
    ): AuditLog {
        $user = $actor === null ? auth()->user() : null;

        return AuditLog::create([
            'occurred_at' => now(),
            'user_id' => $user?->id,
            'actor_name' => $actor ?? $user?->name,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'bid_id' => $bid?->id ?? ($subject instanceof Bid ? $subject->id : null),
            'summary' => Str::limit($summary, 500, ''),
            'properties' => $properties ?: null,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Before/after values for the attributes that are about to be saved. Call before save().
     *
     * @return array<string, array{from: mixed, to: mixed}>
     */
    public static function changes(Model $model, array $only = []): array
    {
        $changes = [];

        foreach ($model->getDirty() as $field => $new) {
            if ($only && ! in_array($field, $only, true)) {
                continue;
            }

            $changes[$field] = [
                'from' => self::plain($model->getOriginal($field)),
                'to' => self::plain($new),
            ];
        }

        return $changes;
    }

    private static function plain(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            is_string($value) => Str::limit($value, 300),
            default => $value,
        };
    }
}