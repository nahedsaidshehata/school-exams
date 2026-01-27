<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');
        $difficulty = (string) $request->query('difficulty', '');
        $subjectId = (string) $request->query('material_id', '');
        $sectionId = (string) $request->query('section_id', '');
        $lessonId = (string) $request->query('lesson_id', '');
        $grade = (string) $request->query('grade', '');

        $query = Question::query()
            ->with(['lesson.section.material'])
            ->withCount('options')
            ->latest();

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('prompt_en', 'like', "%{$q}%")
                    ->orWhere('prompt_ar', 'like', "%{$q}%")
                    ->orWhere('metadata', 'like', "%{$q}%")
                    ->orWhereHas('lesson', function ($l) use ($q) {
                        $l->where('title_en', 'like', "%{$q}%")
                            ->orWhere('title_ar', 'like', "%{$q}%");
                    });
            });
        }

        if ($type !== '') {
            $query->where('type', $type);
        }

        if ($difficulty !== '') {
            $query->where('difficulty', strtoupper($difficulty));
        }

        if ($grade !== '') {
            $query->whereHas('lesson', fn($l) => $l->where('grade', $grade));
        }

        if ($lessonId !== '') {
            $query->where('lesson_id', $lessonId);
        } else {
            if ($sectionId !== '') {
                $query->whereHas('lesson', fn($l) => $l->where('section_id', $sectionId));
            } elseif ($subjectId !== '') {
                $query->whereHas('lesson.section', fn($s) => $s->where('material_id', $subjectId));
            }
        }

        $questions = $query->paginate(15)->appends($request->query());

        $subjects = Material::query()
            ->select('id', 'name_en', 'name_ar')
            ->orderByRaw('COALESCE(name_en, name_ar) ASC')
            ->get();

        $sectionsQuery = Section::query()
            ->select('id', 'material_id', 'title_en', 'title_ar')
            ->orderByRaw('COALESCE(title_en, title_ar) ASC');

        if ($subjectId !== '') {
            $sectionsQuery->where('material_id', $subjectId);
        }
        $sections = $sectionsQuery->get();

        $lessonsQuery = Lesson::query()
            ->select('id', 'section_id', 'title_en', 'title_ar')
            ->orderByRaw('COALESCE(title_en, title_ar) ASC');

        if ($sectionId !== '') {
            $lessonsQuery->where('section_id', $sectionId);
        } elseif ($subjectId !== '') {
            $lessonsQuery->whereHas('section', fn($s) => $s->where('material_id', $subjectId));
        }
        $lessons = $lessonsQuery->get();

        $grades = Lesson::query()
            ->select('grade')
            ->distinct()
            ->whereNotNull('grade')
            ->orderBy('grade')
            ->pluck('grade');

        return view('admin.questions.index', compact(
            'questions',
            'subjects',
            'sections',
            'lessons',
            'grades',
            'q',
            'type',
            'difficulty',
            'subjectId',
            'sectionId',
            'lessonId',
            'grade'
        ));
    }

    public function filters(Request $request)
    {
        $subjectId = (string) $request->query('material_id', '');
        $sectionId = (string) $request->query('section_id', '');
        $grade = (string) $request->query('grade', '');

        $sectionsQuery = Section::query()
            ->select('id', 'material_id', 'title_en', 'title_ar')
            ->orderByRaw('COALESCE(title_en, title_ar) ASC');

        if ($subjectId !== '') {
            $sectionsQuery->where('material_id', $subjectId);
        }
        $sections = $sectionsQuery->get();

        $lessonsQuery = Lesson::query()
            ->select('id', 'section_id', 'title_en', 'title_ar')
            ->orderByRaw('COALESCE(title_en, title_ar) ASC');

        if ($grade !== '') {
            $lessonsQuery->where('grade', $grade);
        }

        if ($sectionId !== '') {
            $lessonsQuery->where('section_id', $sectionId);
        } elseif ($subjectId !== '') {
            $lessonsQuery->whereHas('section', fn($s) => $s->where('material_id', $subjectId));
        }
        $lessons = $lessonsQuery->get();

        return response()->json([
            'sections' => $sections,
            'lessons' => $lessons,
        ]);
    }

    public function create()
    {
        $lessons = Lesson::with('section.material')->get();
        return view('admin.questions.create', compact('lessons'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'lesson_id' => 'required|uuid|exists:lessons,id',
            'type' => 'required|in:MCQ,TF,ESSAY,CLASSIFICATION,REORDER,FILL_BLANK',
            'difficulty' => 'required|in:EASY,MEDIUM,HARD',
            'prompt_en' => 'nullable|string',
            'prompt_ar' => 'required|string',

            // ✅ options only forced for MCQ/TF (REORDER ممكن metadata)
            'options' => 'required_if:type,MCQ,TF|array',
            'options.*.content_en' => 'nullable|string',
            'options.*.content_ar' => 'required_with:options|string',
            'options.*.is_correct' => 'sometimes|boolean',
            'options.*.order_index' => 'sometimes|integer',

            // ✅ metadata can be array
            'metadata' => 'nullable|array',
        ]);

        if ($request->type === 'MCQ') {
            $optionsCount = count($request->options ?? []);
            if ($optionsCount < 2 || $optionsCount > 6) {
                return back()->withInput()->withErrors(['options' => 'MCQ must have between 2 and 6 options.']);
            }
        }

        if ($request->type === 'TF') {
            $optionsCount = count($request->options ?? []);
            if ($optionsCount !== 2) {
                return back()->withInput()->withErrors(['options' => 'True/False must have exactly 2 options.']);
            }
        }

        if (in_array($request->type, ['MCQ', 'TF'], true)) {
            $correctCount = collect($request->options)->where('is_correct', true)->count();
            if ($correctCount !== 1) {
                return back()->withInput()->withErrors(['options' => 'Must have exactly one correct answer.']);
            }
        }

        DB::beginTransaction();
        try {
            // Prepare metadata
            $metadata = $request->input('metadata', []);

            // Merge independent fields into metadata
            if ($request->filled('question_text_ar'))
                $metadata['question_text_ar'] = $request->question_text_ar;
            if ($request->filled('question_text_en'))
                $metadata['question_text_en'] = $request->question_text_en;

            // Save question
            $question = Question::create([
                'lesson_id' => $request->lesson_id,
                'type' => $request->type,
                'difficulty' => strtoupper($request->difficulty),
                'prompt_en' => (string) ($request->prompt_en ?? ''),
                'prompt_ar' => (string) ($request->prompt_ar ?? ''),
                'metadata' => $metadata,
            ]);

            $this->syncOptions($question, $request);

            DB::commit();

            return redirect()->route('admin.questions.index')
                ->with('success', 'Question created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create question: ' . $e->getMessage()]);
        }
    }

    public function show(Question $question)
    {
        $question->load(['lesson.section.material', 'options']);
        return view('admin.questions.show', compact('question'));
    }

    public function edit(Question $question)
    {
        $question->load(['lesson.section.material', 'options']);
        $lessons = Lesson::with('section.material')->get();
        return view('admin.questions.edit', compact('question', 'lessons'));
    }

    public function update(Request $request, Question $question)
    {
        $request->validate([
            'lesson_id' => 'required|uuid|exists:lessons,id',
            'type' => 'required|in:MCQ,TF,ESSAY,CLASSIFICATION,REORDER,FILL_BLANK',
            'difficulty' => 'required|in:EASY,MEDIUM,HARD',
            'prompt_en' => 'nullable|string',
            'prompt_ar' => 'required|string',

            // ✅ options only forced for MCQ/TF
            'options' => 'required_if:type,MCQ,TF|array',
            'options.*.content_en' => 'nullable|string',
            'options.*.content_ar' => 'required_with:options|string',
            'options.*.is_correct' => 'sometimes|boolean',
            'options.*.order_index' => 'sometimes|integer',

            'metadata' => 'nullable|array',
        ]);

        if ($request->type === 'MCQ') {
            $optionsCount = count($request->options ?? []);
            if ($optionsCount < 2 || $optionsCount > 6) {
                return back()->withInput()->withErrors(['options' => 'MCQ must have between 2 and 6 options.']);
            }
        }

        if ($request->type === 'TF') {
            $optionsCount = count($request->options ?? []);
            if ($optionsCount !== 2) {
                return back()->withInput()->withErrors(['options' => 'True/False must have exactly 2 options.']);
            }
        }

        if (in_array($request->type, ['MCQ', 'TF'], true)) {
            $correctCount = collect($request->options)->where('is_correct', true)->count();
            if ($correctCount !== 1) {
                return back()->withInput()->withErrors(['options' => 'Must have exactly one correct answer.']);
            }
        }

        DB::beginTransaction();
        try {
            // Start with existing metadata
            $newMetadata = $question->metadata ?? [];

            // Merge form metadata
            if ($request->has('metadata')) {
                // If the form sends metadata array, likely we want to merge or replace.
                // For deep nested arrays like classification, simple array_merge might be risky if we want to replace lists.
                // The form sends the complete state of classification/reorder, so replacing those keys is usually expected.
                $inputMeta = $request->input('metadata', []);
                $newMetadata = array_merge($newMetadata, $inputMeta);
            }

            // Merge independent fields
            if ($request->has('question_text_ar'))
                $newMetadata['question_text_ar'] = $request->question_text_ar;
            if ($request->has('question_text_en'))
                $newMetadata['question_text_en'] = $request->question_text_en;

            $question->update([
                'lesson_id' => $request->lesson_id,
                'type' => $request->type,
                'difficulty' => strtoupper($request->difficulty),
                'prompt_en' => (string) ($request->prompt_en ?? ''),
                'prompt_ar' => (string) ($request->prompt_ar ?? ''),
                'metadata' => $newMetadata,
            ]);

            $this->syncOptions($question, $request);

            DB::commit();

            return redirect()->route('admin.questions.index')
                ->with('success', 'Question updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update question: ' . $e->getMessage()]);
        }
    }

    public function destroy(Question $question)
    {
        DB::beginTransaction();
        try {
            $question->options()->delete();

            if (Schema::hasTable('exam_questions')) {
                DB::table('exam_questions')->where('question_id', $question->id)->delete();
            }
            if (Schema::hasTable('attempt_answers')) {
                DB::table('attempt_answers')->where('question_id', $question->id)->delete();
            }

            $question->delete();

            DB::commit();
            return redirect()->route('admin.questions.index')
                ->with('success', 'Question deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.questions.index')
                ->with('error', 'Failed to delete question: This question is linked to other records (exams/attempts). Remove it from those first.');
        }
    }

    private function syncOptions(Question $question, Request $request): void
    {
        // ✅ Only MCQ/TF manage options table here
        if (!in_array($question->type, ['MCQ', 'TF'], true)) {
            return;
        }

        $question->options()->delete();

        $options = $request->input('options', []);
        if (!is_array($options))
            return;

        foreach ($options as $index => $option) {
            QuestionOption::create([
                'question_id' => $question->id,
                'content_en' => (string) ($option['content_en'] ?? ''),
                'content_ar' => (string) ($option['content_ar'] ?? ''),
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'order_index' => $option['order_index'] ?? $index,
            ]);
        }
    }
}
