<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DB-04 §3 (M-12 Office, completed 2026-10-11): the office profile — name per language, type
 * (vocabulary office.type), parent office, overview, Head's message, contact, location, hours,
 * own microsite or not — and its sections, services and support contacts (all assignable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_offices', function (Blueprint $table) {
            $table->dropUnique(['name', 'active_key']);
            $table->renameColumn('name', 'name_text');
        });

        Schema::table('office_offices', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('office_offices')->restrictOnDelete();
            $table->foreignId('type_term_id')->nullable()->after('parent_id')->constrained('core_terms')->restrictOnDelete();   // office.type
            $table->json('name')->nullable()->after('type_term_id');
            $table->string('short_name', 50)->nullable()->after('name');
            $table->json('overview')->nullable();
            $table->json('head_message')->nullable();                    // Head = assignment "head"
            $table->string('email', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('extension', 20)->nullable();
            $table->json('location')->nullable();
            $table->unsignedBigInteger('campus_id')->nullable()->index();   // Campus (optional module): index, not FK
            $table->json('office_hours')->nullable();
            $table->boolean('has_microsite')->default(true);              // DIU show_page (FR-OFF-11)
            $table->unsignedInteger('sort_order')->default(0);

            $table->index(['type_term_id', 'is_active', 'sort_order']);
        });

        DB::table('office_offices')->update(['name' => DB::raw("JSON_OBJECT('en', name_text)")]);

        Schema::table('office_offices', fn (Blueprint $table) => $table->dropColumn('name_text'));

        Schema::create('office_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('office_offices')->cascadeOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['office_id', 'is_active', 'sort_order']);
        });

        Schema::create('office_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('office_offices')->cascadeOnDelete();
            $table->json('title');
            $table->json('description')->nullable();
            $table->json('required_documents')->nullable();
            $table->json('fee_note')->nullable();
            $table->decimal('fee_amount', 12, 2)->nullable();
            $table->json('processing_time')->nullable();
            $table->string('apply_url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['office_id', 'is_active', 'sort_order']);
        });

        Schema::create('office_support_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('office_offices')->cascadeOnDelete();
            $table->json('purpose');
            $table->json('location')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();

            $table->index(['office_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_support_contacts');
        Schema::dropIfExists('office_services');
        Schema::dropIfExists('office_sections');

        Schema::table('office_offices', function (Blueprint $table) {
            $table->string('name_text', 191)->nullable();
        });
        DB::table('office_offices')->update(['name_text' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))")]);

        Schema::table('office_offices', function (Blueprint $table) {
            $table->dropIndex(['type_term_id', 'is_active', 'sort_order']);
            $table->dropConstrainedForeignId('parent_id');
            $table->dropConstrainedForeignId('type_term_id');
            $table->dropColumn(['name', 'short_name', 'overview', 'head_message', 'email', 'phone', 'extension', 'location', 'campus_id', 'office_hours', 'has_microsite', 'sort_order']);
        });

        Schema::table('office_offices', function (Blueprint $table) {
            $table->renameColumn('name_text', 'name');
            $table->unique(['name', 'active_key']);
        });
    }
};
