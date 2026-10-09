<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 500);
            $table->text('answer');
            // "Show in" lives in core_relations (IsAttachable), not in a column
            $table->timestamp('published_at')->nullable();                      // HasPublishDate
            $table->unsignedInteger('sort_order')->default(0)->index();         // IX-6
            $table->platformColumns();

            $table->index(['is_active', 'deleted_at', 'published_at']);          // IX-5
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_faqs');
    }
};
