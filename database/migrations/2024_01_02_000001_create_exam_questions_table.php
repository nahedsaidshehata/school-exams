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
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('exam_id');
            $table->uuid('question_id');
            $table->decimal('points', 8, 2);
            $table->integer('order_index');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('restrict');
            
            // Unique constraints
            $table->unique(['exam_id', 'question_id']);
            $table->unique(['exam_id', 'order_index']);
            
            // Indexes
            $table->index('exam_id');
            $table->index('question_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
