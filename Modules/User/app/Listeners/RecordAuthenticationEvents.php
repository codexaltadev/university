<?php

namespace Modules\User\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use Modules\User\Services\LoginLock;

/**
 * Sign-in events in the activity log ("auth"; FR-LOG-02, BR-LOG-02),
 * last login time / IP (FR-USER-08) and the per-account lock (NFR-SEC-03).
 */
class RecordAuthenticationEvents
{
    public function __construct(protected LoginLock $lock) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, [$this, 'login']);
        $events->listen(Logout::class, [$this, 'logout']);
        $events->listen(Failed::class, [$this, 'failed']);
    }

    public function login(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill(['last_login_at' => now(), 'last_login_ip' => request()->ip()])->saveQuietly();
        $this->lock->clear($event->user->email);

        activity('auth')->causedBy($event->user)->performedOn($event->user)->event('login')->log('login');
    }

    public function logout(Logout $event): void
    {
        if ($event->user instanceof User) {
            activity('auth')->causedBy($event->user)->performedOn($event->user)->event('logout')->log('logout');
        }
    }

    /** The tried email is kept; the password never is. */
    public function failed(Failed $event): void
    {
        $email = (string) ($event->credentials['email'] ?? '');
        $this->lock->hit($email);

        activity('auth')
            ->event('login_failed')
            ->withProperties(['email' => $email])
            ->log('login_failed');
    }
}
