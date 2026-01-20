<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_assignments', 'grade')) {
                $table->string('grade')->nullable()->after('assignment_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_assignments', function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }
};
