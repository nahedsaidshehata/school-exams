<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    /**
     * Display a listing of sections (with filters + AJAX search).
     */
    public function index(Request $request)
    {
        $filters = [
            'material_id' => trim((string) $request->query('material_id', '')),
            'q'           => trim((string) $request->query('q', '')),
        ];

        $query = Section::query()->with('material');

        if ($filters['material_id'] !== '') {
            $query->where('material_id', $filters['material_id']);
        }

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($w) use ($q) {
                $w->where('title_en', 'like', "%{$q}%")
                  ->orWhere('title_ar', 'like', "%{$q}%")
                  ->orWhereHas('material', function ($m) use ($q) {
                      $m->where('name_en', 'like', "%{$q}%")
                        ->orWhere('name_ar', 'like', "%{$q}%");
                  });
            });
        }

        $sections = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $materials = Material::orderBy('name_en')->get();

        // ✅ AJAX response: return only HTML fragments
        if ($request->ajax()) {
            $tbody = view('admin.sections.partials.rows', compact('sections'))->render();
            $pagination = view('admin.sections.partials.pagination', compact('sections'))->render();

            return response()->json([
                'tbody'      => $tbody,
                'pagination' => $pagination,
                'total'      => method_exists($sections, 'total') ? $sections->total() : 0,
            ]);
        }

        return view('admin.sections.index', compact('sections', 'materials', 'filters'));
    }

    public function create()
    {
        $materials = Material::orderBy('name_en')->get();
        return view('admin.sections.create', compact('materials'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'material_id' => 'required|uuid|exists:materials,id',
            'title_en' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
        ]);

        Section::create($request->only(['material_id', 'title_en', 'title_ar']));

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section created successfully.');
    }

    public function edit(Section $section)
    {
        $materials = Material::orderBy('name_en')->get();
        return view('admin.sections.edit', compact('section', 'materials'));
    }

    public function update(Request $request, Section $section)
    {
        $request->validate([
            'material_id' => 'required|uuid|exists:materials,id',
            'title_en' => 'required|string|max:255',
            'title_ar' => 'required|string|max:255',
        ]);

        $section->update($request->only(['material_id', 'title_en', 'title_ar']));

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section)
    {
        $section->delete();

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section deleted successfully.');
    }
}
