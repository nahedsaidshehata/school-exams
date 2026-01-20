<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lesson_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lesson_id');
            $table->unsignedBigInteger('uploaded_by')->nullable();

            $table->string('type')->default('CONTENT'); // CONTENT / OTHER
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->string('disk')->default('local');
            $table->string('path');
            $table->unsignedBigInteger('size_bytes')->default(0);

            $table->longText('text_extracted')->nullable();
            $table->string('extraction_status')->default('none'); // none|pending|success|failed
            $table->text('extraction_error')->nullable();

            $table->timestamps();

            $table->index(['lesson_id', 'type']);
            $table->foreign('lesson_id')->references('id')->on('lessons')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_attachments');
    }
};
