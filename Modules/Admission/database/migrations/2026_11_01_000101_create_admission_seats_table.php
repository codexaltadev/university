<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** DB-03 v1.6 §10: the admission test's seat plan (FR-ADM-17) — an applicant's building, room and floor by application serial. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_seats', function (Blueprint $table) {
            $table->id();
            $table->string('application_serial', 50)->index();
            $table->string('student_name', 191);
            $table->unsignedBigInteger('program_id')->nullable()->index();   // Program (optional module): index, not FK
            $table->string('program_label', 191)->nullable();
            $table->string('building', 150);
            $table->string('room', 50);
            $table->string('floor', 50)->nullable();                         // DIU: "Level-6"
            $table->platformColumns();

            $table->unique(['application_serial', 'active_key'], 'admission_seats_serial_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_seats');
    }
};
