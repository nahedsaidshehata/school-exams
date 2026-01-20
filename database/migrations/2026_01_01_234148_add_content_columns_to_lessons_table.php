<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->longText('content_ar')->nullable()->after('title_ar');
            $table->longText('content_en')->nullable()->after('content_ar');
            $table->timestamp('content_updated_at')->nullable()->after('content_en');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['content_ar', 'content_en', 'content_updated_at']);
        });
    }
};
