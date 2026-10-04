<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class TurnstileToken implements ValidationRule
{
    /** Enforced whenever a secret is set, and always in production. */
    public static function enabled(): bool
    {
        return filled(config('services.turnstile.secret_key')) || app()->environment('production');
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret_key');

        if (blank($secret)) {
            report(new RuntimeException('Turnstile secret key is not configured.'));
            $fail('Verification is unavailable right now. Please contact the Procurement department.');

            return;
        }

        try {
            $result = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $value,
                'remoteip' => request()->ip(),
            ])->json();
        } catch (Throwable $e) {
            report($e);
            $fail('We could not complete the verification. Please try again.');

            return;
        }

        if (! ($result['success'] ?? false)) {
            $fail('Verification failed. Please try again.');
        }
    }
}