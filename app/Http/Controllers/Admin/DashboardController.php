<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Question;
use App\Models\School;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $stats = [
            'schools' => School::count(),
            'students' => User::where('role', 'student')->count(),
            'materials' => Material::count(),
            'questions' => Question::count(),
        ];
        
        return view('admin.dashboard', compact('stats'));
    }
}
