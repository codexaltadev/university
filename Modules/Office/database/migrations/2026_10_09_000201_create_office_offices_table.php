<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_offices', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->platformColumns();

            $table->unique(['name', 'active_key']);   // IX-8
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_offices');
    }
};
