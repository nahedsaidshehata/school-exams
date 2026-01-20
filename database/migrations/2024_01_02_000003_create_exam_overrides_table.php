<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('exam_id');
            $table->uuid('school_id');
            $table->uuid('student_id');
            $table->enum('lock_mode', ['LOCK', 'UNLOCK', 'DEFAULT'])->default('DEFAULT');
            $table->dateTime('override_ends_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            
            // Unique constraint
            $table->unique(['exam_id', 'student_id']);
            
            // Indexes
            $table->index('exam_id');
            $table->index('student_id');
            $table->index(['school_id', 'exam_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_overrides');
    }
};
