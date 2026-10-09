<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-01 §4.1 — users is a business (B) table. MFA columns come with C-02 (Filament MFA). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 191)->change();
            $table->string('email', 191)->change();
            $table->string('username', 60)->nullable()->unique()->after('name');
            $table->string('phone', 30)->nullable()->after('password');
            $table->string('locale', 10)->default('en')->after('phone');
            $table->string('timezone', 50)->nullable()->after('locale');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('last_login_at')->nullable()->index();
            $table->string('last_login_ip', 45)->nullable();

            $table->dropTimestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->platformColumns();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'deleted_at']);
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropColumn(['is_active', 'active_key', 'deleted_at', 'created_at', 'updated_at']);
            $table->dropUnique(['username']);
            $table->dropIndex(['last_login_at']);
            $table->dropColumn(['username', 'phone', 'locale', 'timezone', 'invited_at', 'last_login_at', 'last_login_ip']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamps();
        });
    }
};
