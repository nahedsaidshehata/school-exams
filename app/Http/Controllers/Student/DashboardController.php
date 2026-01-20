<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Material;

class DashboardController extends Controller
{
    /**
     * Display the student dashboard.
     */
    public function index()
    {
        $student = auth()->user();
        $school = $student->school;
        
        $materials = Material::with('sections.lessons')->get();
        
        return view('student.dashboard', compact('student', 'school', 'materials'));
    }
}
