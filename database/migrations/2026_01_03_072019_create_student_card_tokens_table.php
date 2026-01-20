<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_card_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();

            // We store ONLY hash for lookup, and encrypted raw token for re-printing.
            $table->string('token_hash', 64)->unique();
            $table->text('token_enc');

            $table->boolean('active')->default(true)->index();
            $table->timestamp('rotated_at')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_card_tokens');
    }
};
