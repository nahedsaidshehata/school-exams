<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\ExamOverride;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\School;
use App\Models\User;
use App\Models\Material;
use App\Models\Section;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    /**
     * Display a listing of exams (supports optional filter by lesson_id)
     */
    public function index(Request $request)
    {
        $lessonId = trim((string) $request->query('lesson_id', ''));

        $filterLesson = null;
        if ($lessonId !== '') {
            $filterLesson = Lesson::with(['section.material'])->findOrFail($lessonId);
        }

        $query = Exam::query()->withCount('examQuestions');

        if ($lessonId !== '') {
            $query->whereHas('questions', function ($q) use ($lessonId) {
                $q->where('lesson_id', $lessonId);
            })->distinct('exams.id');
        }

        $exams = $query
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->appends($request->query());

        return view('admin.exams.index', compact('exams', 'lessonId', 'filterLesson'));
    }

    /**
     * Show the form for creating a new exam
     */
    public function create()
    {
        return view('admin.exams.create');
    }

    /**
     * Store a newly created exam
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title_en'           => 'required|string|max:255',
            'title_ar'           => 'required|string|max:255',
            'duration_minutes'   => 'required|integer|min:1',
            'starts_at'          => 'required|date',
            'ends_at'            => 'required|date|after:starts_at',
            'max_attempts'       => 'required|integer|min:1',
            'is_globally_locked' => 'boolean',
        ]);

        $validated['id'] = Str::uuid();
        $validated['is_globally_locked'] = $request->has('is_globally_locked');

        $exam = Exam::create($validated);

        return redirect()->route('admin.exams.show', $exam->id)
            ->with('success', 'Exam created successfully.');
    }

    public function edit(string $exam)
    {
        $exam = Exam::findOrFail($exam);

        return view('admin.exams.edit', compact('exam'));
    }

    public function update(Request $request, string $exam)
    {
        $exam = Exam::findOrFail($exam);

        $validated = $request->validate([
            'title_en'           => 'required|string|max:255',
            'title_ar'           => 'required|string|max:255',
            'duration_minutes'   => 'required|integer|min:1',
            'starts_at'          => 'required|date',
            'ends_at'            => 'required|date|after:starts_at',
            'max_attempts'       => 'required|integer|min:1',
            'is_globally_locked' => 'boolean',
        ]);

        $validated['is_globally_locked'] = $request->has('is_globally_locked');

        $exam->update($validated);

        return redirect()
            ->route('admin.exams.show', $exam->id)
            ->with('success', 'Exam updated successfully.');
    }


    /**
     * Display the specified exam
     */
    public function show(Exam $exam)
    {
        $exam->load([
            'examQuestions.question.options',
            'assignments.school',
            'assignments.student',
            'overrides.student'
        ]);

        $subjects = Material::orderBy('name_en')->get();

        $sections = collect();
        $lessons  = collect();
        $availableQuestions = collect();

        $schools = School::orderBy('name_en')->get();

        $students = User::where('role', 'student')
            ->with('school')
            ->orderBy('full_name')
            ->get();

        /**
         * ✅ Grades list (Legacy / fallback)
         * الأفضل: نعتمد على AJAX gradesPicker بناءً على school_id
         * لكن نخلي ده موجود كاحتياطي.
         */
        $grades = collect();
        $gradeColumn = null;

        if (Schema::hasTable('student_profiles') && Schema::hasColumn('student_profiles', 'grade')) {
            // from student_profiles (preferred)
            $gradeColumn = 'student_profiles.grade';

            $grades = DB::table('student_profiles')
                ->whereNotNull('grade')
                ->select('grade')
                ->distinct()
                ->orderBy('grade')
                ->pluck('grade')
                ->values();
        } else {
            // fallback: users.grade or users.year
            if (Schema::hasColumn('users', 'grade')) {
                $gradeColumn = 'users.grade';
            } elseif (Schema::hasColumn('users', 'year')) {
                $gradeColumn = 'users.year';
            }

            if ($gradeColumn) {
                $col = str_contains($gradeColumn, '.') ? $gradeColumn : $gradeColumn;
                $plainCol = str_contains($col, '.') ? explode('.', $col)[1] : $col;

                $grades = User::where('role', 'student')
                    ->whereNotNull($plainCol)
                    ->select($plainCol)
                    ->distinct()
                    ->orderBy($plainCol)
                    ->pluck($plainCol)
                    ->values();
            }
        }

        return view('admin.exams.show', compact(
            'exam',
            'availableQuestions',
            'schools',
            'students',
            'subjects',
            'sections',
            'lessons',
            'grades',
            'gradeColumn'
        ));
    }

    /**
     * AJAX: Cascading filters + available questions for this exam
     */
    public function examQuestionsPicker(Request $request, Exam $exam)
    {
        $request->validate([
            'q'           => 'nullable|string|max:200',
            'type'        => 'nullable|string|in:MCQ,TF,ESSAY,CLASSIFICATION,REORDER,FILL_BLANK',
            'difficulty'  => 'nullable|string|in:EASY,MEDIUM,HARD',
            'material_id' => 'nullable|uuid|exists:materials,id',
            'section_id'  => 'nullable|uuid|exists:sections,id',
            'lesson_id'   => 'nullable|uuid|exists:lessons,id',
        ]);

        $materialId = $request->input('material_id');
        $sectionId  = $request->input('section_id');
        $lessonId   = $request->input('lesson_id');

        $sectionsQuery = Section::query()->orderBy('title_en');
        if ($materialId) {
            $sectionsQuery->where('material_id', $materialId);
        }
        $sections = $sectionsQuery->get(['id','material_id','title_en','title_ar']);

        $lessonsQuery = Lesson::query()
            ->with('section')
            ->orderBy('title_en');

        if ($materialId) {
            $lessonsQuery->whereHas('section', function ($q) use ($materialId) {
                $q->where('material_id', $materialId);
            });
        }
        if ($sectionId) {
            $lessonsQuery->where('section_id', $sectionId);
        }

        $lessons = $lessonsQuery->get(['id','section_id','title_en','title_ar']);

        $already = ExamQuestion::where('exam_id', $exam->id)->pluck('question_id');

        $qBuilder = Question::query()
            ->with(['lesson.section.material'])
            ->whereNotIn('id', $already);

        if ($lessonId) {
            $qBuilder->where('lesson_id', $lessonId);
        } else {
            if ($sectionId) {
                $qBuilder->whereHas('lesson', function ($q) use ($sectionId) {
                    $q->where('section_id', $sectionId);
                });
            }
            if ($materialId) {
                $qBuilder->whereHas('lesson.section', function ($q) use ($materialId) {
                    $q->where('material_id', $materialId);
                });
            }
        }

        if ($request->filled('type')) {
            $qBuilder->where('type', $request->input('type'));
        }
        if ($request->filled('difficulty')) {
            $qBuilder->where('difficulty', $request->input('difficulty'));
        }

        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $qBuilder->where(function ($qq) use ($term) {
                $qq->where('prompt_en', 'like', "%{$term}%")
                   ->orWhere('prompt_ar', 'like', "%{$term}%")
                   ->orWhereHas('lesson', function ($lq) use ($term) {
                       $lq->where('title_en', 'like', "%{$term}%")
                          ->orWhere('title_ar', 'like', "%{$term}%");
                   })
                   ->orWhere('metadata', 'like', "%{$term}%");
            });
        }

        $questions = $qBuilder
            ->latest()
            ->take(300)
            ->get(['id','lesson_id','type','difficulty','prompt_en','prompt_ar']);

        return response()->json([
            'sections' => $sections,
            'lessons'  => $lessons,
            'questions'=> $questions->map(function ($q) {
                return [
                    'id'         => $q->id,
                    'type'       => $q->type,
                    'difficulty' => $q->difficulty,
                    'prompt_en'  => $q->prompt_en,
                    'prompt_ar'  => $q->prompt_ar,
                    'lesson_id'  => $q->lesson_id,
                ];
            }),
        ]);
    }

    /**
     * Add question(s) to the exam
     */
    public function addQuestion(Request $request, Exam $exam)
    {
        $request->validate([
            'question_id'    => ['nullable', 'uuid', 'exists:questions,id'],
            'question_ids'   => ['nullable', 'array', 'min:1'],
            'question_ids.*' => ['uuid', 'exists:questions,id'],
            'points'         => ['required', 'numeric', 'min:0.01'],
            'order_index'    => ['nullable', 'integer', 'min:1'],
        ]);

        $ids = [];

        if ($request->filled('question_ids')) {
            $ids = array_values(array_filter((array) $request->input('question_ids')));
        } elseif ($request->filled('question_id')) {
            $ids = [(string) $request->input('question_id')];
        }

        if (count($ids) === 0) {
            $msg = __('Please select at least one question.');
            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $ids = array_values(array_unique($ids));

        $already = ExamQuestion::where('exam_id', $exam->id)
            ->whereIn('question_id', $ids)
            ->pluck('question_id')
            ->toArray();

        $ids = array_values(array_diff($ids, $already));

        if (count($ids) === 0) {
            $msg = __('All selected questions are already added to this exam.');
            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $points = (float) $request->input('points');

        $maxOrder   = ExamQuestion::where('exam_id', $exam->id)->max('order_index') ?? 0;
        $startOrder = (int) ($request->input('order_index') ?? ($maxOrder + 1));

        $n = count($ids);

        ExamQuestion::where('exam_id', $exam->id)
            ->where('order_index', '>=', $startOrder)
            ->increment('order_index', $n);

        foreach ($ids as $i => $qid) {
            ExamQuestion::create([
                'exam_id'     => $exam->id,
                'question_id' => $qid,
                'points'      => $points,
                'order_index' => $startOrder + $i,
            ]);
        }

        if ($request->expectsJson()) {
            $totalPoints = ExamQuestion::where('exam_id', $exam->id)->sum('points');
            $count       = ExamQuestion::where('exam_id', $exam->id)->count();

            return response()->json([
                'message' => __('Added :n question(s) successfully.', ['n' => $n]),
                'data' => [
                    'stats' => [
                        'count'        => $count,
                        'total_points' => (float) $totalPoints,
                        'next_order'   => $count + 1,
                    ],
                ],
            ], 200);
        }

        return back()->with('success', 'Added ' . $n . ' question(s) successfully.');
    }

    /**
     * Remove a question from the exam
     */
    public function removeQuestion(Exam $exam, Question $question)
    {
        ExamQuestion::where('exam_id', $exam->id)
            ->where('question_id', $question->id)
            ->delete();

        return back()->with('success', 'Question removed from exam successfully.');
    }

    /**
     * Create exam assignments
     */
    public function createAssignment(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'assignment_type' => 'required|in:SCHOOL,GRADE,STUDENT',
            'school_id'       => 'required_if:assignment_type,SCHOOL|required_if:assignment_type,GRADE|uuid|exists:schools,id',
            'grade'           => 'required_if:assignment_type,GRADE|string|max:50',
            'student_ids'     => 'required_if:assignment_type,STUDENT|array|min:1',
            'student_ids.*'   => 'uuid|exists:users,id',
        ]);

        // SCHOOL
        if ($validated['assignment_type'] === 'SCHOOL') {

            $exists = ExamAssignment::where('exam_id', $exam->id)
                ->where('school_id', $validated['school_id'])
                ->where('assignment_type', 'SCHOOL')
                ->exists();

            if ($exists) {
                return back()->with('error', 'This school is already assigned to this exam.');
            }

            ExamAssignment::unguarded(function () use ($exam, $validated) {
                ExamAssignment::create([
                    'id'              => Str::uuid(),
                    'exam_id'         => $exam->id,
                    'school_id'       => $validated['school_id'],
                    'assignment_type' => 'SCHOOL',
                    'grade'           => null,
                    'student_id'      => null,
                    'created_by'      => auth()->id(),
                ]);
            });

            return back()->with('success', 'Exam assigned to school successfully.');
        }

        // GRADE
        if ($validated['assignment_type'] === 'GRADE') {

            $exists = ExamAssignment::where('exam_id', $exam->id)
                ->where('school_id', $validated['school_id'])
                ->where('assignment_type', 'GRADE')
                ->where('grade', $validated['grade'])
                ->exists();

            if ($exists) {
                return back()->with('error', 'This grade is already assigned to this exam for the selected school.');
            }

            // NOTE: this will still fail if DB CHECK constraint doesn't allow "GRADE"
            ExamAssignment::unguarded(function () use ($exam, $validated) {
                ExamAssignment::create([
                    'id'              => Str::uuid(),
                    'exam_id'         => $exam->id,
                    'school_id'       => $validated['school_id'],
                    'assignment_type' => 'GRADE',
                    'grade'           => $validated['grade'],
                    'student_id'      => null,
                    'created_by'      => auth()->id(),
                ]);
            });

            return back()->with('success', 'Exam assigned to grade successfully.');
        }

        // STUDENT
        $count = 0;

        foreach ($validated['student_ids'] as $studentId) {
            $student = User::find($studentId);

            if (!$student || $student->role !== 'student' || !$student->school_id) {
                continue;
            }

            $exists = ExamAssignment::where('exam_id', $exam->id)
                ->where('school_id', $student->school_id)
                ->where('student_id', $studentId)
                ->where('assignment_type', 'STUDENT')
                ->exists();

            if (!$exists) {
                $studentGrade = null;

                if (Schema::hasTable('student_profiles') && Schema::hasColumn('student_profiles', 'grade')) {
                    $studentGrade = DB::table('student_profiles')
                        ->where('user_id', $studentId)
                        ->value('grade');
                }

                ExamAssignment::unguarded(function () use ($exam, $student, $studentId, $studentGrade) {
                    ExamAssignment::create([
                        'id'              => Str::uuid(),
                        'exam_id'         => $exam->id,
                        'school_id'       => $student->school_id,
                        'assignment_type' => 'STUDENT',
                        'grade'           => $studentGrade ? (string) $studentGrade : null,
                        'student_id'      => $studentId,
                        'created_by'      => auth()->id(),
                    ]);
                });

                $count++;
            }
        }

        return back()->with('success', "Exam assigned to {$count} student(s) successfully.");
    }

    /**
     * Create or update exam override
     */
    public function createOverride(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'student_id'       => 'required|uuid|exists:users,id',
            'lock_mode'        => 'required|in:LOCK,UNLOCK,DEFAULT',
            'override_ends_at' => 'nullable|date|after:' . $exam->starts_at,
        ]);

        $student = User::find($validated['student_id']);

        if ($student->role !== 'student' || !$student->school_id) {
            return back()->with('error', 'Invalid student selected.');
        }

        ExamOverride::updateOrCreate(
            [
                'exam_id'    => $exam->id,
                'student_id' => $validated['student_id'],
            ],
            [
                'id'              => Str::uuid(),
                'school_id'       => $student->school_id,
                'lock_mode'       => $validated['lock_mode'],
                'override_ends_at'=> $validated['override_ends_at'],
            ]
        );

        return back()->with('success', 'Override created/updated successfully.');
    }

    /**
     * AJAX: Grades for a selected school
     */
    public function gradesPicker(Request $request, Exam $exam)
    {
        $request->validate([
            'school_id' => 'required|uuid|exists:schools,id',
        ]);

        $schoolId = (string) $request->input('school_id');

        // Preferred source: student_profiles.grade
        $grades = collect();

        if (Schema::hasTable('student_profiles') && Schema::hasColumn('student_profiles', 'grade')) {
            $grades = User::where('users.role', 'student')
                ->where('users.school_id', $schoolId)
                ->join('student_profiles', 'student_profiles.user_id', '=', 'users.id')
                ->whereNotNull('student_profiles.grade')
                ->select('student_profiles.grade')
                ->distinct()
                ->orderBy('student_profiles.grade')
                ->pluck('student_profiles.grade')
                ->values();
        }

        return response()->json([
            'grades' => $grades,
        ]);
    }
}
