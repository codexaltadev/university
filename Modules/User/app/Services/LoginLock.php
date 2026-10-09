<?php

namespace Modules\User\Services;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Per-account lock after repeated wrong passwords (NFR-SEC-03):
 * config('platform.security.login_max_attempts') within the lockout window.
 */
class LoginLock
{
    public function hit(string $email): void
    {
        RateLimiter::hit($this->key($email), $this->minutes() * 60);
    }

    public function clear(string $email): void
    {
        RateLimiter::clear($this->key($email));
    }

    public function isLocked(string $email): bool
    {
        return RateLimiter::tooManyAttempts($this->key($email), (int) config('platform.security.login_max_attempts', 5));
    }

    public function minutesLeft(string $email): int
    {
        return (int) ceil(RateLimiter::availableIn($this->key($email)) / 60);
    }

    protected function key(string $email): string
    {
        return 'login-lock:'.Str::lower(trim($email));
    }

    protected function minutes(): int
    {
        return (int) config('platform.security.login_lockout_minutes', 15);
    }
}
