<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    /**
     * Display a listing of materials.
     */
    public function index()
    {
        $materials = Material::withCount('sections')->latest()->paginate(15);
        
        return view('admin.materials.index', compact('materials'));
    }

    /**
     * Show the form for creating a new material.
     */
    public function create()
    {
        return view('admin.materials.create');
    }

    /**
     * Store a newly created material.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
        ]);

        Material::create($request->only(['name_en', 'name_ar']));

        return redirect()->route('admin.materials.index')
            ->with('success', 'Material created successfully.');
    }

    /**
     * Show the form for editing the specified material.
     */
    public function edit(Material $material)
    {
        return view('admin.materials.edit', compact('material'));
    }

    /**
     * Update the specified material.
     */
    public function update(Request $request, Material $material)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
        ]);

        $material->update($request->only(['name_en', 'name_ar']));

        return redirect()->route('admin.materials.index')
            ->with('success', 'Material updated successfully.');
    }

    /**
     * Remove the specified material.
     */
    public function destroy(Material $material)
    {
        $material->delete();

        return redirect()->route('admin.materials.index')
            ->with('success', 'Material deleted successfully.');
    }
}
