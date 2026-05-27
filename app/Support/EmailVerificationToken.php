<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EmailVerificationToken
{
    private const CACHE_PREFIX = 'email-verification:';

    public static function issue(User $user): string
    {
        $token = Str::random(64);

        Cache::put(
            self::cacheKey($user),
            hash('sha256', $token),
            now()->addMinutes((int) config('auth.verification.expire', 60)),
        );

        return $token;
    }

    public static function validate(User $user, string $token): bool
    {
        if (strlen($token) < 32) {
            return false;
        }

        $expected = Cache::get(self::cacheKey($user));

        if (! is_string($expected)) {
            return false;
        }

        return hash_equals($expected, hash('sha256', $token));
    }

    public static function forget(User $user): void
    {
        Cache::forget(self::cacheKey($user));
    }

    private static function cacheKey(User $user): string
    {
        return self::CACHE_PREFIX.$user->getKey();
    }
}
