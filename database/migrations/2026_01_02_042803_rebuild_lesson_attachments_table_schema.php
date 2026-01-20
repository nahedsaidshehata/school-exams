<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Since attachments are currently failing to insert, we rebuild the table cleanly.
        Schema::dropIfExists('lesson_attachments');

        Schema::create('lesson_attachments', function (Blueprint $table) {
            $table->id(); // integer auto increment (matches your older setup)

            $table->uuid('lesson_id')->index();

            $table->string('original_name');
            $table->string('disk')->default('public');
            $table->string('path');

            $table->string('mime_type')->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);

            $table->string('sha256', 64)->nullable()->index();

            // IMPORTANT: your users.id appears to be UUID
            $table->uuid('uploaded_by')->nullable()->index();

            $table->string('extraction_status', 20)->default('IDLE');
            $table->text('extraction_error')->nullable();

            $table->longText('extracted_text')->nullable();
            $table->timestamp('extracted_text_updated_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_attachments');
    }
};
