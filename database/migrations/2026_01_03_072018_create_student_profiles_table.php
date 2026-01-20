<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // one-to-one with users
            $table->uuid('user_id')->unique();

            // required by you (two columns)
            $table->string('year')->nullable();   // مثال: 2025-2026
            $table->string('grade')->nullable();  // مثال: 6 أو "Grade 6"

            $table->enum('gender', ['male','female'])->nullable();
            $table->boolean('send')->default(false); // Special needs
            $table->string('parent_email')->nullable();
            $table->string('nationality')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // optional indexes for reports
            $table->index(['grade']);
            $table->index(['year']);
            $table->index(['gender']);
            $table->index(['send']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
