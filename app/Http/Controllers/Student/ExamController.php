<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Services\ExamStateResolver;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    protected $stateResolver;

    public function __construct(ExamStateResolver $stateResolver)
    {
        $this->stateResolver = $stateResolver;
    }

    /**
     * Display a listing of exams assigned to this student with resolved state
     */
    public function index()
    {
        $student = auth()->user();
        $schoolId = $student->school_id;

        // ✅ Get student's grade from student_profiles
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

        // Get exams assigned directly to this student
        $directExamIds = ExamAssignment::where('school_id', $schoolId)
            ->where('assignment_type', 'STUDENT')
            ->where('student_id', $student->id)
            ->pluck('exam_id');

        // Get exams assigned to the student's school
        $schoolExamIds = ExamAssignment::where('school_id', $schoolId)
            ->where('assignment_type', 'SCHOOL')
            ->pluck('exam_id');

        // ✅ Get exams assigned to the student's grade (within the same school)
        $gradeExamIds = collect();
        if (!empty($studentGrade)) {
            $gradeExamIds = ExamAssignment::where('school_id', $schoolId)
                ->where('assignment_type', 'GRADE')
                ->where('grade', $studentGrade)
                ->pluck('exam_id');
        }

        // Merge and get unique exam IDs
        $examIds = $directExamIds
            ->merge($schoolExamIds)
            ->merge($gradeExamIds)
            ->unique()
            ->values();

        // Get exams
        $exams = Exam::whereIn('id', $examIds)
            ->withCount('examQuestions')
            ->orderBy('starts_at', 'desc')
            ->get();

        // ✅ Get list of exam IDs that the student has submitted at least once
        // Statuses that count as "Submitted": SUBMITTED, PENDING_MANUAL, GRADED
        $submittedExamIds = \App\Models\ExamAttempt::where('student_id', $student->id)
            ->whereIn('status', ['SUBMITTED', 'PENDING_MANUAL', 'GRADED'])
            ->pluck('exam_id')
            ->toArray();

        // ✅ Get list of exam IDs that are currently IN_PROGRESS
        $startedExamIds = \App\Models\ExamAttempt::where('student_id', $student->id)
            ->where('status', 'IN_PROGRESS')
            ->pluck('exam_id')
            ->toArray();

        // Resolve state for each exam
        $examsWithState = $exams->map(function ($exam) use ($student, $submittedExamIds, $startedExamIds) {
            $exam->state = $this->stateResolver->resolveState($exam, $student);
            $exam->state_icon = $this->stateResolver->getStateIcon($exam->state);
            $exam->state_badge = $this->stateResolver->getStateBadgeClass($exam->state);

            // Mark if submitted or started
            $exam->is_submitted = in_array($exam->id, $submittedExamIds);
            $exam->is_started = in_array($exam->id, $startedExamIds);

            // Determine status for UI
            if ($exam->is_submitted) {
                $exam->status = 'submitted';
            } elseif ($exam->is_started) {
                $exam->status = 'in_progress';
            } elseif ($exam->state === 'EXPIRED') {
                $exam->status = 'expired';
            } elseif ($exam->state === 'AVAILABLE') {
                $exam->status = 'available';
            } elseif ($exam->state === 'UPCOMING') {
                $exam->status = 'upcoming';
            } else {
                $exam->status = strtolower($exam->state);
            }

            return $exam;
        });

        // ✅ Filter by status
        if (request()->has('status') && !empty(request('status'))) {
            $status = request('status');
            $examsWithState = $examsWithState->filter(function ($exam) use ($status) {
                if ($status === 'available') {
                    // Available: New exams (not submitted AND not started) AND time is valid (AVAILABLE)
                    return $exam->state === 'AVAILABLE' && !$exam->is_submitted && !$exam->is_started;
                }
                if ($status === 'submitted') {
                    // Submitted: Finished at least once.
                    return $exam->is_submitted;
                }
                if ($status === 'expired') {
                    // Expired: Time finished.
                    return $exam->state === 'EXPIRED';
                }
                if ($status === 'in_progress') {
                    return $exam->is_started;
                }
                // For other statuses or 'all', return everything (or maybe filter 'upcoming'?)
                // User only specified logic for these three.
                return true;
            });
        }

        // ✅ Filter by search query (title)
        if (request()->has('q') && !empty(request('q'))) {
            $search = strtolower(trim(request('q')));
            $examsWithState = $examsWithState->filter(function ($exam) use ($search) {
                return str_contains(strtolower($exam->title_en), $search) ||
                    str_contains(strtolower($exam->title_ar), $search);
            });
        }

        // ✅ IMPORTANT: Pass both variable names to avoid breaking the Blade view
        return view('student.exams.index', [
            'exams' => $examsWithState,          // most templates expect $exams
            'examsWithState' => $examsWithState, // keep your new name too
        ]);
    }

    /**
     * Display the specified exam with state (read-only, no correct answers, no points)
     */
    public function show(Exam $exam)
    {
        $student = auth()->user();
        $schoolId = $student->school_id;

        // Verify this exam is assigned to this student
        $isAssigned = $this->stateResolver->isAssignedToStudent($exam, $student);

        if (!$isAssigned) {
            abort(403, 'This exam is not assigned to you.');
        }

        // Resolve exam state
        $state = $this->stateResolver->resolveState($exam, $student);

        // Get override if exists - SECURITY: Scope by both student_id AND school_id
        $override = $exam->overrides()
            ->where('student_id', $student->id)
            ->where('school_id', $schoolId)
            ->first();

        // Get state message
        $stateMessage = $this->stateResolver->getStateMessage($state, $exam, $override);
        $stateIcon = $this->stateResolver->getStateIcon($state);
        $stateBadge = $this->stateResolver->getStateBadgeClass($state);

        // Load exam with questions (but NO correct answers, NO points)
        $exam->load([
            'examQuestions' => function ($query) {
                $query->select('exam_questions.id', 'exam_questions.exam_id', 'exam_questions.question_id', 'exam_questions.order_index')
                    ->orderBy('exam_questions.order_index');
            },
            'examQuestions.question' => function ($query) {
                $query->select('id', 'type', 'difficulty', 'prompt_en', 'prompt_ar');
            },
            'examQuestions.question.options' => function ($query) {
                $query->select('id', 'question_id', 'content_en', 'content_ar', 'order_index')
                    ->orderBy('order_index');
            }
        ]);

        // ✅ Check if student has an active attempt
        $activeAttempt = \App\Models\ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('school_id', $student->school_id)
            ->where('status', 'IN_PROGRESS')
            ->first();

        // ✅ Get attempt count
        $attemptCount = \App\Models\ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('school_id', $student->school_id)
            ->count();

        $attemptsUsed = $attemptCount;
        $attemptsLimit = $exam->max_attempts ?? null;

        return view('student.exams.show', compact(
            'exam',
            'state',
            'stateMessage',
            'stateIcon',
            'stateBadge',
            'override',
            'activeAttempt',
            'attemptsUsed',
            'attemptsLimit'
        ));
    }
}
