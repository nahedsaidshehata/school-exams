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
        Schema::create('exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title_en');
            $table->string('title_ar');
            $table->integer('duration_minutes');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->integer('max_attempts')->default(5);
            $table->boolean('is_globally_locked')->default(false);
            $table->timestamps();
            
            // Indexes
            $table->index('starts_at');
            $table->index('ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
