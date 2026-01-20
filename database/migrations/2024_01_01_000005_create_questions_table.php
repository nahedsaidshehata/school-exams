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
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lesson_id');
            $table->enum('type', [
                'MCQ',
                'TF',
                'ESSAY',
                'CLASSIFICATION',
                'REORDER',
                'FILL_BLANK',
            ]);

            $table->enum('difficulty', ['EASY', 'MEDIUM', 'HARD']);
            $table->text('prompt_en');
            $table->text('prompt_ar');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('lesson_id')->references('id')->on('lessons')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
