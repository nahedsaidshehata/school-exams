<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite: we must rebuild table to change enum/check constraints
        DB::statement('PRAGMA foreign_keys=OFF');

        // ✅ Safety: if a previous failed migration left temp table behind
        Schema::dropIfExists('exam_assignments_new');

        // 1) Create new table with the desired schema
        Schema::create('exam_assignments_new', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('exam_id');
            $table->uuid('school_id');

            // ✅ now includes GRADE
            $table->enum('assignment_type', ['SCHOOL', 'GRADE', 'STUDENT']);

            // ✅ new grade column for GRADE (and optional for STUDENT)
            $table->string('grade', 50)->nullable();

            $table->uuid('student_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // ✅ Uniques (new names to avoid SQLite global index-name collisions)
            // SCHOOL: one per exam+school
            $table->unique(['exam_id', 'school_id', 'assignment_type'], 'uniq_exam_assign_school_v2');

            // GRADE: one per exam+school+grade
            $table->unique(['exam_id', 'school_id', 'assignment_type', 'grade'], 'uniq_exam_assign_grade_v2');

            // STUDENT: one per exam+school+student
            $table->unique(['exam_id', 'school_id', 'assignment_type', 'student_id'], 'uniq_exam_assign_student_v2');

            // Indexes
            $table->index('exam_id');
            $table->index('school_id');
            $table->index('student_id');
            $table->index('grade');
            $table->index(['exam_id', 'assignment_type', 'school_id']);
            $table->index(['exam_id', 'assignment_type', 'student_id']);
            $table->index(['exam_id', 'assignment_type', 'grade']);
        });

        // 2) Copy data from old table (SCHOOL/STUDENT only). grade will be NULL.
        DB::statement("
            INSERT INTO exam_assignments_new
                (id, exam_id, school_id, assignment_type, grade, student_id, created_by, created_at, updated_at)
            SELECT
                id, exam_id, school_id, assignment_type, NULL as grade, student_id, created_by, created_at, updated_at
            FROM exam_assignments
        ");

        // 3) Drop old table and rename new
        Schema::drop('exam_assignments');
        Schema::rename('exam_assignments_new', 'exam_assignments');

        DB::statement('PRAGMA foreign_keys=ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');

        // ✅ Safety: if a previous failed rollback left temp table behind
        Schema::dropIfExists('exam_assignments_old');

        // rollback to original shape (SCHOOL/STUDENT only, no grade column)
        Schema::create('exam_assignments_old', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('exam_id');
            $table->uuid('school_id');
            $table->enum('assignment_type', ['SCHOOL', 'STUDENT']);
            $table->uuid('student_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            // ✅ IMPORTANT: do NOT reuse old global index names in SQLite
            $table->unique(['exam_id', 'school_id', 'assignment_type'], 'uniq_exam_assign_school_down_v2');

            $table->index('exam_id');
            $table->index('school_id');
            $table->index('student_id');
            $table->index(['exam_id', 'assignment_type', 'school_id']);
            $table->index(['exam_id', 'assignment_type', 'student_id']);
        });

        // copy only SCHOOL/STUDENT rows, ignore GRADE rows
        DB::statement("
            INSERT INTO exam_assignments_old
                (id, exam_id, school_id, assignment_type, student_id, created_by, created_at, updated_at)
            SELECT
                id, exam_id, school_id, assignment_type, student_id, created_by, created_at, updated_at
            FROM exam_assignments
            WHERE assignment_type IN ('SCHOOL','STUDENT')
        ");

        Schema::drop('exam_assignments');
        Schema::rename('exam_assignments_old', 'exam_assignments');

        DB::statement('PRAGMA foreign_keys=ON');
    }
};
