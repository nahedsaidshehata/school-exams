<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modify existing table (older migration already created it)
        Schema::table('lesson_attachments', function (Blueprint $table) {
            // Relation
            if (!Schema::hasColumn('lesson_attachments', 'lesson_id')) {
                $table->uuid('lesson_id')->index()->after('id');
            }

            // File info
            if (!Schema::hasColumn('lesson_attachments', 'original_name')) {
                $table->string('original_name')->after('lesson_id');
            }
            if (!Schema::hasColumn('lesson_attachments', 'disk')) {
                $table->string('disk')->default('public')->after('original_name');
            }
            if (!Schema::hasColumn('lesson_attachments', 'path')) {
                $table->string('path')->after('disk');
            }
            if (!Schema::hasColumn('lesson_attachments', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('path');
            }
            if (!Schema::hasColumn('lesson_attachments', 'extension')) {
                $table->string('extension', 20)->nullable()->after('mime_type');
            }
            if (!Schema::hasColumn('lesson_attachments', 'size_bytes')) {
                $table->unsignedBigInteger('size_bytes')->default(0)->after('extension');
            }
            if (!Schema::hasColumn('lesson_attachments', 'sha256')) {
                $table->string('sha256', 64)->nullable()->index()->after('size_bytes');
            }

            // Uploader (assume users.id is BIGINT default Laravel)
            if (!Schema::hasColumn('lesson_attachments', 'uploaded_by')) {
                $table->unsignedBigInteger('uploaded_by')->nullable()->index()->after('sha256');
            }

            // Extraction fields
            if (!Schema::hasColumn('lesson_attachments', 'extraction_status')) {
                $table->string('extraction_status', 20)->default('IDLE')->after('uploaded_by'); // IDLE|QUEUED|PROCESSING|SUCCESS|FAILED
            }
            if (!Schema::hasColumn('lesson_attachments', 'extraction_error')) {
                $table->text('extraction_error')->nullable()->after('extraction_status');
            }
            if (!Schema::hasColumn('lesson_attachments', 'extracted_text')) {
                $table->longText('extracted_text')->nullable()->after('extraction_error');
            }
            if (!Schema::hasColumn('lesson_attachments', 'extracted_text_updated_at')) {
                $table->timestamp('extracted_text_updated_at')->nullable()->after('extracted_text');
            }
        });

        /**
         * IMPORTANT (SQLite):
         * SQLite has limited ALTER TABLE support for adding foreign keys after creation.
         * So we do NOT add FK constraints here to avoid migration failure.
         * We'll rely on app-level integrity and cascade handling in code for now.
         *
         * If you later switch to MySQL, we can add a separate migration to add FKs.
         */
    }

    public function down(): void
    {
        // Roll back ONLY what we added (do not drop the table, because older migration "owns" creation)
        Schema::table('lesson_attachments', function (Blueprint $table) {
            $columns = [
                'lesson_id',
                'original_name',
                'disk',
                'path',
                'mime_type',
                'extension',
                'size_bytes',
                'sha256',
                'uploaded_by',
                'extraction_status',
                'extraction_error',
                'extracted_text',
                'extracted_text_updated_at',
            ];

            foreach ($columns as $col) {
                try { $table->dropColumn($col); } catch (\Throwable $e) {}
            }
        });
    }
};
