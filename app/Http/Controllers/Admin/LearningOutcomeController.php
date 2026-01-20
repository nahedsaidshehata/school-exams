<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningOutcome;
use App\Models\Material;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LearningOutcomeController extends Controller
{
    public function index(Request $request)
    {
        $query = LearningOutcome::query()->orderBy('created_at', 'desc');

        // Keep your optional filters (for future UI)
        if ($request->filled('material_id')) $query->where('material_id', $request->material_id);
        if ($request->filled('section_id'))  $query->where('section_id', $request->section_id);
        if ($request->filled('grade_level')) $query->where('grade_level', $request->grade_level);

        // Support both `q` (new UI) and `search` (old)
        $s = trim((string) ($request->input('q') ?? $request->input('search') ?? ''));
        if ($s !== '') {
            $query->where(function ($qq) use ($s) {
                $qq->where('code', 'like', "%{$s}%")
                    ->orWhere('title_ar', 'like', "%{$s}%")
                    ->orWhere('title_en', 'like', "%{$s}%");
            });
        }

        $outcomes = $query->paginate(20)->withQueryString();

        // These are optional (your current 3 views don't use them, but keep for future filters)
        $materials = class_exists(Material::class) ? Material::orderBy('name_en')->get() : collect();
        // FIX: sections most likely have title_en, not name_en
        $sections  = class_exists(Section::class) ? Section::orderBy('title_en')->get() : collect();

        return view('admin.learning_outcomes.index', compact('outcomes', 'materials', 'sections'));
    }

    public function create()
    {
        $materials = class_exists(Material::class) ? Material::orderBy('name_en')->get() : collect();
        $sections  = class_exists(Section::class) ? Section::orderBy('title_en')->get() : collect();

        return view('admin.learning_outcomes.create', compact('materials', 'sections'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'material_id' => ['nullable', 'uuid'],
            'section_id'  => ['nullable', 'uuid'],

            'code'     => ['nullable', 'string', 'max:255', Rule::unique('learning_outcomes', 'code')],
            'title_ar' => ['required', 'string', 'max:255'],
            // our views require title_en — keep it required (better for bilingual UI)
            'title_en' => ['required', 'string', 'max:255'],

            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'grade_level'    => ['nullable', 'string', 'max:50'],
        ]);

        LearningOutcome::create($data);

        // FIX: correct route name (underscore)
        return redirect()->route('admin.learning_outcomes.index')
            ->with('success', __('Learning outcome created successfully.'));
    }

    public function edit(LearningOutcome $learning_outcome)
    {
        $materials = class_exists(Material::class) ? Material::orderBy('name_en')->get() : collect();
        $sections  = class_exists(Section::class) ? Section::orderBy('title_en')->get() : collect();

        // IMPORTANT: match the variable name expected by the views I sent: $outcome
        $outcome = $learning_outcome;

        return view('admin.learning_outcomes.edit', compact('outcome', 'materials', 'sections'));
    }

    public function update(Request $request, LearningOutcome $learning_outcome)
    {
        $data = $request->validate([
            'material_id' => ['nullable', 'uuid'],
            'section_id'  => ['nullable', 'uuid'],

            'code'     => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('learning_outcomes', 'code')->ignore($learning_outcome->id),
            ],
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],

            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'grade_level'    => ['nullable', 'string', 'max:50'],
        ]);

        $learning_outcome->update($data);

        // FIX: correct route name (underscore)
        return redirect()->route('admin.learning_outcomes.index')
            ->with('success', __('Learning outcome updated successfully.'));
    }

    public function destroy(LearningOutcome $learning_outcome)
    {
        // Defensive: if linked to lessons, prevent deletion (only if relation exists)
        if (method_exists($learning_outcome, 'lessons')) {
            try {
                if ($learning_outcome->lessons()->exists()) {
                    return redirect()->route('admin.learning_outcomes.index')
                        ->with('error', __('Cannot delete: this outcome is linked to lessons.'));
                }
            } catch (\Throwable $e) {
                // ignore relation errors if not configured properly yet
            }
        }

        $learning_outcome->delete();

        return redirect()->route('admin.learning_outcomes.index')
            ->with('success', __('Learning outcome deleted successfully.'));
    }
}
