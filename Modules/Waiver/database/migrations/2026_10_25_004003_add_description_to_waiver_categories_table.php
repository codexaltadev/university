<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DB-03 v1.11: description — one line the calculator shows under the category's name
 * ("By SSC and HSC GPA"); empty = only the name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waiver_categories', function (Blueprint $table) {
            $table->json('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('waiver_categories', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
