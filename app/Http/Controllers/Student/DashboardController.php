<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\ExamAttempt;
use App\Services\ExamStateResolver;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $stateResolver;

    public function __construct(ExamStateResolver $stateResolver)
    {
        $this->stateResolver = $stateResolver;
    }

    /**
     * Display the student dashboard.
     */
    public function index()
    {
        $student = auth()->user();
        $school = $student->school;
        $schoolId = $student->school_id;

        // 1. Get student's grade
        $studentGrade = null;
        if (method_exists($student, 'studentProfile')) {
            $studentGrade = optional($student->studentProfile)->grade;
        }
        if ($studentGrade === null) {
            $studentGrade = DB::table('student_profiles')
                ->where('user_id', $student->id)
                ->value('grade');
        }
        $studentGrade = $studentGrade !== null ? trim((string) $studentGrade) : null;

        // 2. Get all assigned exams
        $directExamIds = ExamAssignment::where('school_id', $schoolId)
            ->where('assignment_type', 'STUDENT')
            ->where('student_id', $student->id)
            ->pluck('exam_id');

        $schoolExamIds = ExamAssignment::where('school_id', $schoolId)
            ->where('assignment_type', 'SCHOOL')
            ->pluck('exam_id');

        $gradeExamIds = collect();
        if (!empty($studentGrade)) {
            $gradeExamIds = ExamAssignment::where('school_id', $schoolId)
                ->where('assignment_type', 'GRADE')
                ->where('grade', $studentGrade)
                ->pluck('exam_id');
        }

        $examIds = $directExamIds
            ->merge($schoolExamIds)
            ->merge($gradeExamIds)
            ->unique();

        $exams = Exam::whereIn('id', $examIds)->get();

        // 3. Resolve states and count
        $availableCount = 0;
        $submittedCount = 0;
        $expiredCount = 0;

        foreach ($exams as $exam) {
            $state = $this->stateResolver->resolveState($exam, $student);

            // Check if already submitted
            $hasSubmitted = ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->whereIn('status', ['SUBMITTED', 'PENDING_MANUAL', 'GRADED'])
                ->exists();

            if ($hasSubmitted) {
                $submittedCount++;
            } elseif ($state === 'AVAILABLE') {
                $availableCount++;
            } elseif ($state === 'EXPIRED') {
                $expiredCount++;
            }
        }

        $materials = Material::with('sections.lessons')->get();

        return view('student.dashboard', compact(
            'student',
            'school',
            'materials',
            'availableCount',
            'submittedCount',
            'expiredCount'
        ));
    }
}
