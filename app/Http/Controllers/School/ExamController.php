<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAssignment;

class ExamController extends Controller
{
    /**
     * Display a listing of exams assigned to this school
     */
    public function index()
    {
        // Get school_id from authenticated user (tenant context)
        $schoolId = auth()->user()->school_id;

        // Get exams assigned to this school
        $examIds = ExamAssignment::where('school_id', $schoolId)
            ->where('assignment_type', 'SCHOOL')
            ->pluck('exam_id');

        $exams = Exam::whereIn('id', $examIds)
            ->withCount('examQuestions')
            ->orderBy('starts_at', 'desc')
            ->get();

        return view('school.exams.index', compact('exams'));
    }

    /**
     * Display the specified exam (read-only, no correct answers, no points)
     */
    public function show(Exam $exam)
    {
        // Get school_id from authenticated user (tenant context)
        $schoolId = auth()->user()->school_id;

        // Verify this exam is assigned to this school
        $isAssigned = ExamAssignment::where('exam_id', $exam->id)
            ->where('school_id', $schoolId)
            ->where('assignment_type', 'SCHOOL')
            ->exists();

        if (!$isAssigned) {
            abort(403, 'This exam is not assigned to your school.');
        }

        // Load exam with questions (but NO correct answers, NO points)
        // SECURITY PATCH: Explicitly select only safe pivot columns to prevent points leakage
        $exam->load([
            'examQuestions' => function ($query) {
                // CRITICAL: Select only safe columns from exam_questions pivot table
                // DO NOT include 'points' column
                $query->select('exam_questions.id', 'exam_questions.exam_id', 'exam_questions.question_id', 'exam_questions.order_index')
                    ->orderBy('exam_questions.order_index');
            },
            'examQuestions.question' => function ($query) {
                $query->select('id', 'type', 'difficulty', 'prompt_en', 'prompt_ar');
            },
            'examQuestions.question.options' => function ($query) {
                // CRITICAL: Do NOT include is_correct field
                $query->select('id', 'question_id', 'content_en', 'content_ar', 'order_index')
                    ->orderBy('order_index');
            }
        ]);

        return view('school.exams.show', compact('exam'));
    }
}
