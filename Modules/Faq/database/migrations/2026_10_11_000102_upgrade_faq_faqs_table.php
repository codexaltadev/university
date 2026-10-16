<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DB-05 v1.1 §13: question and answer per language (JSON; existing text becomes English),
 * "Was this helpful?" counts, and no publish date — shown when is_active (owner, 2026-10-11).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faq_faqs', function (Blueprint $table) {
            $table->renameColumn('question', 'question_text');
            $table->renameColumn('answer', 'answer_text');
        });

        Schema::table('faq_faqs', function (Blueprint $table) {
            $table->json('question')->nullable()->after('id');
            $table->json('answer')->nullable()->after('question');
            $table->unsignedInteger('helpful_yes')->default(0)->after('answer');
            $table->unsignedInteger('helpful_no')->default(0)->after('helpful_yes');
        });

        DB::table('faq_faqs')->update([
            'question' => DB::raw("JSON_OBJECT('en', question_text)"),
            'answer' => DB::raw("JSON_OBJECT('en', answer_text)"),
        ]);

        Schema::table('faq_faqs', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'deleted_at', 'published_at']);
            $table->dropColumn(['question_text', 'answer_text', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('faq_faqs', function (Blueprint $table) {
            $table->string('question_text', 500)->nullable();
            $table->text('answer_text')->nullable();
            $table->timestamp('published_at')->nullable();
        });

        DB::table('faq_faqs')->update([
            'question_text' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(question, '$.en'))"),
            'answer_text' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(answer, '$.en'))"),
        ]);

        Schema::table('faq_faqs', function (Blueprint $table) {
            $table->dropColumn(['question', 'answer', 'helpful_yes', 'helpful_no']);
        });

        Schema::table('faq_faqs', function (Blueprint $table) {
            $table->renameColumn('question_text', 'question');
            $table->renameColumn('answer_text', 'answer');
            $table->index(['is_active', 'deleted_at', 'published_at']);
        });
    }
};
