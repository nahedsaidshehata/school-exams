<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            // خليها nullable لو الامتحانات “مركزية” وممكن تتخصص بعدين
            $table->uuid('school_id')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex(['school_id']);
            $table->dropColumn('school_id');
        });
    }
};
