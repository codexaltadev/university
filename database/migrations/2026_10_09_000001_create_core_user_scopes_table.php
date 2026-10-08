<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_user_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('scopeable_type', 100);   // morph alias, e.g. department.department
            $table->unsignedBigInteger('scopeable_id');
            $table->timestamps();

            $table->unique(['user_id', 'scopeable_type', 'scopeable_id']);
            $table->index(['scopeable_type', 'scopeable_id']);   // IX-1: polymorphic reference, no FK
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_user_scopes');
    }
};
