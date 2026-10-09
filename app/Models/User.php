<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Platform\Concerns\Blameable;
use Platform\Concerns\HasActiveFlag;
use Platform\Concerns\LogsPlatformActivity;
use Platform\Models\UserScope;
use Spatie\Permission\Traits\HasRoles;

/**
 * Admin panel user (DB-01 §4.1) — a business table, so it gets the same
 * platform behaviour as BaseModel (it cannot extend it, being Authenticatable).
 */
#[Fillable(['name', 'username', 'email', 'password', 'phone', 'locale', 'timezone', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use Blameable, HasActiveFlag, HasFactory, HasRoles, LogsPlatformActivity, Notifiable, SoftDeletes;

    use InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery;   // 2FA (TOTP), NFR-SEC-04

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invited_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Inactive users cannot log in (FR-USER-05). */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(config('filament-shield.super_admin.name'));
    }

    /** Data scope (P-03): which entities this user works on. Empty = unrestricted. */
    public function scopes(): HasMany
    {
        return $this->hasMany(UserScope::class);
    }
}
