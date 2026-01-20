<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Display the school dashboard.
     */
    public function index()
    {
        $school = auth()->user()->school;
        $studentsCount = User::where('school_id', $school->id)
            ->where('role', 'student')
            ->count();
        
        $materials = Material::withCount('sections')->get();
        $materialsCount = Material::count();
        
        $students = User::where('school_id', $school->id)
            ->where('role', 'student')
            ->get();
        
        return view('school.dashboard', compact('school', 'studentsCount', 'materialsCount', 'materials', 'students'));
    }
}
