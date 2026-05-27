<?php

namespace App\Support;

use App\Models\User;

class VerificationUrl
{
    public static function forUser(User $user, string $token): string
    {
        $path = route('verification.verify', [
            'id' => $user->getKey(),
            'token' => $token,
        ], false);

        return self::applicationOrigin().$path;
    }

    public static function applicationOrigin(): string
    {
        if (! app()->runningInConsole() && request()->getHttpHost() !== '') {
            return request()->getSchemeAndHttpHost();
        }

        return rtrim((string) config('app.url'), '/');
    }
}
