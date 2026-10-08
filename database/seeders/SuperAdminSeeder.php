<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * First Super Admin (Deployment Plan §4, step 3).
 * Email / password from .env; when no password is set, a random one is
 * generated and printed once. Re-running never changes an existing password.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::findOrCreate(config('filament-shield.super_admin.name'), config('auth.defaults.guard'));

        $email = env('SUPER_ADMIN_EMAIL', 'admin@university.test');
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $password = env('SUPER_ADMIN_PASSWORD') ?: Str::password(16, symbols: false);

            $user = User::query()->create([
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'email' => $email,
                'password' => $password,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $this->command?->info("Super Admin created: {$email}");

            if (! env('SUPER_ADMIN_PASSWORD')) {
                $this->command?->warn("Generated password (shown once): {$password}");
            }
        }

        $user->assignRole($role);
    }
}
