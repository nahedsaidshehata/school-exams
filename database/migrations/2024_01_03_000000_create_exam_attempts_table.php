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
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->uuid('student_id');
            $table->uuid('exam_id');
            $table->integer('attempt_number');
            $table->enum('status', ['IN_PROGRESS', 'SUBMITTED', 'PENDING_MANUAL', 'GRADED'])->default('IN_PROGRESS');
            $table->integer('reset_version')->default(0);
            $table->string('active_session_token')->nullable();
            $table->dateTime('last_heartbeat')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('submitted_at')->nullable();
            $table->decimal('max_possible_score', 8, 2);
            $table->decimal('raw_score', 8, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            
            // Indexes
            $table->index('school_id');
            $table->index('student_id');
            $table->index('exam_id');
            $table->index('active_session_token');
            $table->index('last_heartbeat');
            $table->index('status');
            
            // Unique constraint
            $table->unique(['student_id', 'exam_id', 'attempt_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
