<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

return new class extends Migration {
    public function up(): void
    {
        try {
            Schema::table('exam_questions', function (Blueprint $table) {
                $table->unique(['exam_id', 'question_id'], 'exam_questions_exam_id_question_id_unique');
            });
        } catch (QueryException $e) {
            // SQLite: index already exists -> ignore
            if (str_contains($e->getMessage(), 'already exists')) {
                return;
            }
            throw $e;
        }
    }

    public function down(): void
    {
        try {
            Schema::table('exam_questions', function (Blueprint $table) {
                $table->dropUnique('exam_questions_exam_id_question_id_unique');
            });
        } catch (QueryException $e) {
            // SQLite: if it doesn't exist -> ignore
            if (str_contains($e->getMessage(), 'no such index') || str_contains($e->getMessage(), 'does not exist')) {
                return;
            }
            throw $e;
        }
    }
};
