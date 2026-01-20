<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;

class ExamRoomController extends Controller
{
    /**
     * Show exam introduction page
     * GET /student/exams/{exam}/intro
     */
    public function showIntro(Request $request, Exam $exam)
    {
        $student = $request->user();

        // Verify exam belongs to student's school
        if (isset($exam->school_id) && $exam->school_id !== $student->school_id) {
            abort(404);
        }

        // Check if student has an active attempt
        $activeAttempt = ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('school_id', $student->school_id)
            ->where('status', 'IN_PROGRESS')
            ->first();

        // Load exam with questions count (kept as-is)
        $exam->load('examQuestions');

        // Get attempt count
        $attemptCount = ExamAttempt::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where('school_id', $student->school_id)
            ->count();

        // Provide consistent variables for the view
        $attemptsUsed = $attemptCount;
        $attemptsLimit = $exam->max_attempts ?? null;

        return view('student.exams.intro', compact(
            'exam',
            'activeAttempt',
            'attemptCount',
            'attemptsUsed',
            'attemptsLimit'
        ));
    }

    /**
     * Show exam room (taking exam)
     * GET /student/attempts/{attempt}/room
     */
    public function room(Request $request, ExamAttempt $attempt)
    {
        $student = $request->user();

        // Verify attempt belongs to student
        if ($attempt->student_id !== $student->id || $attempt->school_id !== $student->school_id) {
            abort(404);
        }

        // Check if attempt is still in progress
        if ($attempt->status !== 'IN_PROGRESS') {
            return redirect()->route('student.exams.show', $attempt->exam_id)
                ->with('error', 'هذه المحاولة قد انتهت بالفعل');
        }

        // Load exam (basic usage in the room view)
        $exam = $attempt->exam;

        // Calculate time remaining
        $expiresAt = $attempt->started_at->copy()->addMinutes($exam->duration_minutes);
        $remainingSeconds = max(0, now()->diffInSeconds($expiresAt, false));

        // Endpoint to fetch questions securely (no correct answers / no points in UI)
        $questionsEndpoint = url("/student/exams/{$exam->id}/questions");

        // Render the room using the existing student exam view (enhanced to act as room when $attempt exists)
        return view('student.exams.show', compact(
            'attempt',
            'exam',
            'remainingSeconds',
            'questionsEndpoint'
        ));
    }
}
