<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-03 v1.5 §10 (M-09 Admission): circulars, highlights, steps, contact groups, result
 * batches and their results, subscribers. Shown when is_active — no publish date; the session
 * is text until the Session module (M-05) comes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_circulars', function (Blueprint $table) {
            $table->id();
            $table->string('session_label', 50)->nullable();   // "Fall 2026"
            $table->json('title');
            $table->json('content')->nullable();
            $table->date('apply_starts_on')->nullable();
            $table->date('apply_ends_on')->nullable()->index();
            $table->date('test_on')->nullable();
            $table->date('admission_on')->nullable();
            $table->date('classes_start_on')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->platformColumns();
        });

        Schema::create('admission_highlights', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->text('url')->nullable();
            $table->boolean('open_in_new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });

        Schema::create('admission_steps', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });

        Schema::create('admission_contact_groups', function (Blueprint $table) {
            $table->id();
            $table->json('name');   // "Local Admission", "International Admission"; people → core_assignments
            $table->unsignedInteger('sort_order')->default(0);
            $table->platformColumns();
        });

        Schema::create('admission_result_batches', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('session_label', 50)->nullable();
            $table->json('messages')->nullable();   // {selected|not_selected|waiting|absent|not_found: {en: …}}; {name} = the student
            $table->unsignedInteger('imported_rows')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->platformColumns();             // is_active = published (FR-ADM-08)
        });

        Schema::create('admission_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('admission_result_batches')->cascadeOnDelete();   // a batch goes with its results (BRL-ADM-01)
            $table->unsignedBigInteger('program_id')->nullable()->index();                             // Program (optional module): index, not FK
            $table->string('program_label', 191)->nullable();
            $table->string('application_serial', 50)->index();
            $table->string('student_name', 191);
            $table->string('result', 20);                 // selected, not_selected, waiting, absent
            $table->string('result_label', 100)->nullable();   // the file's own words: "Waiting 2nd Merit List"
            $table->platformColumns();

            $table->unique(['batch_id', 'application_serial', 'active_key'], 'admission_results_serial_unique');
        });

        Schema::create('admission_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20);                // email, whatsapp
            $table->string('contact', 191);
            $table->string('topic', 50)->default('admission');
            $table->string('source', 30)->default('admission');   // admission, footer
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('unsubscribe_reason', 191)->nullable();
            $table->platformColumns();

            $table->unique(['channel', 'contact', 'topic', 'active_key'], 'admission_subscribers_contact_unique');
        });
    }

    public function down(): void
    {
        foreach (['admission_subscribers', 'admission_results', 'admission_result_batches', 'admission_contact_groups', 'admission_steps', 'admission_highlights', 'admission_circulars'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
