<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Material;
use App\Models\LearningOutcome;
use Illuminate\Http\Request;
use App\Models\Exam;


class LessonController extends Controller
{
    /**
     * Display a listing of lessons (with filters + AJAX search).
     */
    public function index(Request $request)
    {
        $filters = [
            'material_id' => trim((string)$request->query('material_id', '')),
            'section_id' => trim((string)$request->query('section_id', '')),
            'q' => trim((string)$request->query('q', '')),
        ];

        $query = Lesson::query()
            ->with(['section.material'])
            ->withCount('learningOutcomes');

        // Filter: material via section.material_id
        if ($filters['material_id'] !== '') {
            $query->whereHas('section', function ($s) use ($filters) {
                $s->where('material_id', $filters['material_id']);
            });
        }

        // Filter: section_id
        if ($filters['section_id'] !== '') {
            $query->where('section_id', $filters['section_id']);
        }

        // Search: lesson title + section/material names
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q) {
                $w->where('title_en', 'like', "%{$q}%")
                    ->orWhere('title_ar', 'like', "%{$q}%")
                    ->orWhereHas('section', function ($s) use ($q) {
                        $s->where('title_en', 'like', "%{$q}%")
                            ->orWhere('title_ar', 'like', "%{$q}%")
                            ->orWhereHas('material', function ($m) use ($q) {
                                $m->where('name_en', 'like', "%{$q}%")
                                    ->orWhere('name_ar', 'like', "%{$q}%");
                            });
                    });
            });
        }

        $lessons = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Dropdown options
        $materials = Material::orderBy('name_en')->get();

        // For initial load only (non-AJAX): if material selected, load its sections
        $sections = collect();
        if ($filters['material_id'] !== '') {
            $sections = Section::where('material_id', $filters['material_id'])
                ->orderBy('title_en')
                ->get();
        }

        // ✅ AJAX: return only fragments
        if ($request->ajax()) {
            $tbody = view('admin.lessons.partials.rows', compact('lessons'))->render();
            $pagination = view('admin.lessons.partials.pagination', compact('lessons'))->render();

            return response()->json([
                'tbody' => $tbody,
                'pagination' => $pagination,
                'total' => method_exists($lessons, 'total') ? $lessons->total() : 0,
            ]);
        }

        return view('admin.lessons.index', compact('lessons', 'materials', 'sections', 'filters'));
    }

    /**
     * Show the form for creating a new lesson.
     */
    public function create()
    {
        $materials = Material::orderBy('name_en')->get();

        $selectedMaterialId = old('material_id');
        $sections = collect();

        if (!empty($selectedMaterialId)) {
            $sections = Section::with('material')
                ->where('material_id', $selectedMaterialId)
                ->orderBy('title_en')
                ->get();
        }

        $learningOutcomes = LearningOutcome::orderBy('title_ar')->get();

        return view('admin.lessons.create', compact('materials', 'sections', 'learningOutcomes', 'selectedMaterialId'));
    }

    /**
     * Store a newly created lesson.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'material_id' => 'required|uuid|exists:materials,id',
            'section_id' => 'required|uuid|exists:sections,id',
            'title_en' => 'nullable|string|max:255|required_without:title_ar|min:3|max:255',
            'title_ar' => 'nullable|string|max:255|required_without:title_en|min:3|max:255',
            'grade' => 'required|numeric',
            'content_ar' => ['nullable', 'string'],
            'content_en' => ['nullable', 'string'],
            'learning_outcome_ids' => ['nullable', 'array'],
            'learning_outcome_ids.*' => ['uuid', 'exists:learning_outcomes,id'],
        ]);

        $validSection = Section::where('id', $data['section_id'])
            ->where('material_id', $data['material_id'])
            ->exists();

        if (!$validSection) {
            return back()
                ->withInput()
                ->withErrors(['section_id' => 'Selected section does not belong to the chosen subject.']);
        }

        $lessonData = [
            'section_id' => $data['section_id'],
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'],
            'grade' => $data['grade'],
            'content_ar' => $data['content_ar'] ?? null,
            'content_en' => $data['content_en'] ?? null,
        ];

        if (!empty($lessonData['content_ar']) || !empty($lessonData['content_en'])) {
            $lessonData['content_updated_at'] = now();
        }

        $lesson = Lesson::create($lessonData);

        $outcomeIds = $data['learning_outcome_ids'] ?? [];
        $syncData = [];
        foreach ($outcomeIds as $oid) {
            $syncData[$oid] = ['weight' => 3, 'notes' => null];
        }
        $lesson->learningOutcomes()->sync($syncData);

        return redirect()->route('admin.lessons.index')
            ->with('success', 'Lesson created successfully.');
    }

    public function edit(Lesson $lesson)
    {
        $materials = Material::orderBy('name_en')->get();
        $sections = Section::with('material')->get();
        $learningOutcomes = LearningOutcome::orderBy('title_ar')->get();

        $lesson->load([
            'learningOutcomes',
            'attachments' => function ($q) {
                $q->latest();
            },
            'section.material',
        ]);

        return view('admin.lessons.edit', compact('lesson', 'sections', 'learningOutcomes', 'materials'));
    }

    public function update(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'material_id' => 'nullable|uuid|exists:materials,id',
            'section_id' => 'required|uuid|exists:sections,id',
            'title_en' => 'nullable|string|max:255|required_without:title_ar|min:3|max:255',
            'title_ar' => 'nullable|string|max:255|required_without:title_en|min:3|max:255',
            'content_ar' => ['nullable', 'string'],
            'grade' => 'required|numeric',
            'content_en' => ['nullable', 'string'],
            'learning_outcome_ids' => ['nullable', 'array'],
            'learning_outcome_ids.*' => ['uuid', 'exists:learning_outcomes,id'],
        ]);

        if (!empty($data['material_id'])) {
            $validSection = Section::where('id', $data['section_id'])
                ->where('material_id', $data['material_id'])
                ->exists();

            if (!$validSection) {
                return back()
                    ->withInput()
                    ->withErrors(['section_id' => 'Selected section does not belong to the chosen subject.']);
            }
        }

        $incomingAr = $data['content_ar'] ?? null;
        $incomingEn = $data['content_en'] ?? null;

        $contentChanged =
            ($lesson->content_ar ?? null) !== $incomingAr ||
            ($lesson->content_en ?? null) !== $incomingEn;

        $updateData = [
            'section_id' => $data['section_id'],
            'title_en' => $data['title_en'],
            'title_ar' => $data['title_ar'],
            'grade' => $data['grade'],
            'content_ar' => $incomingAr,
            'content_en' => $incomingEn,
        ];

        if ($contentChanged) {
            $updateData['content_updated_at'] = now();
        }

        $lesson->update($updateData);

        $outcomeIds = $data['learning_outcome_ids'] ?? [];
        $syncData = [];
        foreach ($outcomeIds as $oid) {
            $syncData[$oid] = ['weight' => 3, 'notes' => null];
        }
        $lesson->learningOutcomes()->sync($syncData);

        return redirect()->route('admin.lessons.index')
            ->with('success', 'Lesson updated successfully.');
    }

    public function destroy(Lesson $lesson)
    {
        $lesson->delete();

        return redirect()->route('admin.lessons.index')
            ->with('success', 'Lesson deleted successfully.');
    }

    public function show(Lesson $lesson)
    {
        $lesson->load([
            'section.material',
            'learningOutcomes',
            'attachments' => function ($q) {
                $q->latest();
            },
        ]);

        $lesson->loadCount('questions');

        $examsCount = Exam::query()
            ->whereHas('questions', function ($q) use ($lesson) {
                $q->where('lesson_id', $lesson->id);
            })
            ->distinct('exams.id')
            ->count('exams.id');

        return view('admin.lessons.show', compact('lesson', 'examsCount'));
    }


}
