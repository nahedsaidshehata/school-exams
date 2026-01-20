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
        Schema::create('exam_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('exam_id');
            $table->uuid('school_id');
            $table->enum('assignment_type', ['SCHOOL', 'STUDENT']);
            $table->uuid('student_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            // Unique constraints
            // For SCHOOL type: unique per exam + school + type
            $table->unique(['exam_id', 'school_id', 'assignment_type'], 'unique_school_assignment');
            
            // Indexes
            $table->index('exam_id');
            $table->index('school_id');
            $table->index('student_id');
            $table->index(['exam_id', 'assignment_type', 'school_id']);
            $table->index(['exam_id', 'assignment_type', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_assignments');
    }
};
