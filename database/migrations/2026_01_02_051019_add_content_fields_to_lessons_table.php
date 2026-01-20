<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'content_ar')) {
                $table->longText('content_ar')->nullable();
            }
            if (!Schema::hasColumn('lessons', 'content_en')) {
                $table->longText('content_en')->nullable();
            }
            if (!Schema::hasColumn('lessons', 'content_updated_at')) {
                $table->timestamp('content_updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            foreach (['content_ar','content_en','content_updated_at'] as $col) {
                try { $table->dropColumn($col); } catch (\Throwable $e) {}
            }
        });
    }
};
