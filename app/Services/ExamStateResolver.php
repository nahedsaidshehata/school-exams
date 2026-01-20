<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamOverride;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExamStateResolver
{
    /**
     * Resolve the exam state for a specific student
     *
     * States: LOCKED, UPCOMING, AVAILABLE, EXPIRED
     * Priority:
     * 1. LOCKED (highest priority)
     * 2. UPCOMING
     * 3. EXPIRED
     * 4. AVAILABLE (default)
     */
    public function resolveState(Exam $exam, User $student): string
    {
        $now = Carbon::now();

        // Step 1: Fetch override if exists
        // SECURITY: Scope by exam_id, student_id, AND school_id for tenant isolation
        $override = ExamOverride::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->where('school_id', $student->school_id)
            ->first();

        // Step 2: Determine lock status
        $isLocked = $this->determineIfLocked($exam, $override);

        // Step 3: Return LOCKED if locked (highest priority)
        if ($isLocked) {
            return 'LOCKED';
        }

        // Step 4: Determine effective end time
        // Use override deadline if present, otherwise use exam's end date
        $effectiveEndsAt = $override && $override->override_ends_at
            ? $override->override_ends_at
            : $exam->ends_at;

        // Step 5: Check timing (priority: UPCOMING > EXPIRED > AVAILABLE)
        if ($now->lt($exam->starts_at)) {
            return 'UPCOMING';
        }

        if ($now->gt($effectiveEndsAt)) {
            return 'EXPIRED';
        }

        // Step 6: Default state
        return 'AVAILABLE';
    }

    /**
     * Determine if exam is locked for the student
     *
     * Priority:
     * 1. If override.lock_mode = 'LOCK' → LOCKED
     * 2. If override.lock_mode = 'UNLOCK' → UNLOCKED
     * 3. If override.lock_mode = 'DEFAULT' → Use exam.is_globally_locked
     * 4. If no override → Use exam.is_globally_locked
     */
    private function determineIfLocked(Exam $exam, ?ExamOverride $override): bool
    {
        if ($override) {
            if ($override->lock_mode === 'LOCK') {
                return true;
            }

            if ($override->lock_mode === 'UNLOCK') {
                return false;
            }

            // lock_mode === 'DEFAULT'
            return $exam->is_globally_locked;
        }

        // No override, use exam's global setting
        return $exam->is_globally_locked;
    }

    /**
     * Check if student is assigned to this exam
     *
     * SECURITY: All checks scoped by student's school_id from authenticated user
     *
     * Assignment logic:
     * 1. Direct student assignment: assignment_type='STUDENT' AND student_id matches AND school_id matches
     * 2. School-wide assignment: assignment_type='SCHOOL' AND school_id matches
     * 3. Grade assignment: assignment_type='GRADE' AND grade matches (from student_profiles) AND school_id matches
     */
    public function isAssignedToStudent(Exam $exam, User $student): bool
    {
        // SECURITY: Use school_id from authenticated student object, never from request
        $schoolId = $student->school_id;

        // 1) Direct student assignment
        $directAssignment = $exam->assignments()
            ->where('assignment_type', 'STUDENT')
            ->where('student_id', $student->id)
            ->where('school_id', $schoolId)
            ->exists();

        if ($directAssignment) {
            return true;
        }

        // 2) School-wide assignment
        $schoolAssignment = $exam->assignments()
            ->where('assignment_type', 'SCHOOL')
            ->where('school_id', $schoolId)
            ->exists();

        if ($schoolAssignment) {
            return true;
        }

        // 3) Grade assignment (grade comes from student_profiles, not users)
        $studentGrade = null;

        // If relation exists, use it; otherwise fallback to DB query
        if (method_exists($student, 'studentProfile')) {
            $studentGrade = optional($student->studentProfile)->grade;
        }

        if ($studentGrade === null) {
            $studentGrade = DB::table('student_profiles')
                ->where('user_id', $student->id)
                ->value('grade');
        }

        $studentGrade = $studentGrade !== null ? trim((string) $studentGrade) : null;

        if (empty($studentGrade)) {
            return false;
        }

        $gradeAssignment = $exam->assignments()
            ->where('assignment_type', 'GRADE')
            ->where('grade', $studentGrade)
            ->where('school_id', $schoolId)
            ->exists();

        return $gradeAssignment;
    }

    /**
     * Get state message for display
     *
     * SECURITY: Does not expose sensitive data (points, correct answers)
     */
    public function getStateMessage(string $state, Exam $exam, ?ExamOverride $override = null): string
    {
        switch ($state) {
            case 'LOCKED':
                return 'This exam is currently locked and cannot be accessed.';

            case 'UPCOMING':
                return 'Exam will be available on ' . $exam->starts_at->format('F j, Y \a\t g:i A');

            case 'EXPIRED':
                $endsAt = $override && $override->override_ends_at
                    ? $override->override_ends_at
                    : $exam->ends_at;
                return 'Exam deadline has passed on ' . $endsAt->format('F j, Y \a\t g:i A');

            case 'AVAILABLE':
                return 'You can view this exam.';

            default:
                return '';
        }
    }

    /**
     * Get state badge class for UI
     */
    public function getStateBadgeClass(string $state): string
    {
        return match ($state) {
            'LOCKED' => 'badge-danger',
            'UPCOMING' => 'badge-info',
            'AVAILABLE' => 'badge-success',
            'EXPIRED' => 'badge-secondary',
            default => 'badge-light',
        };
    }

    /**
     * Get state icon for UI
     */
    public function getStateIcon(string $state): string
    {
        return match ($state) {
            'LOCKED' => '🔒',
            'UPCOMING' => '🔵',
            'AVAILABLE' => '🟢',
            'EXPIRED' => '🔴',
            default => '⚪',
        };
    }
}
