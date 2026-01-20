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
        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('attempt_id');
            $table->uuid('question_id');
            $table->integer('reset_version')->default(0);
            $table->json('student_response');
            $table->decimal('points_awarded', 8, 2)->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('attempt_id')->references('id')->on('exam_attempts')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
            
            // Indexes
            $table->index('attempt_id');
            $table->index('question_id');
            $table->index('reset_version');
            
            // Unique constraint
            $table->unique(['attempt_id', 'question_id', 'reset_version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
    }
};
