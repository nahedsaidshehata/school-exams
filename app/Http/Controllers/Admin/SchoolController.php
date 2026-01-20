<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SchoolController extends Controller
{
    /**
     * Display a listing of schools (with search + AJAX live search).
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $schoolsQuery = School::query()
            ->with('schoolUser')
            ->latest();

        if ($q !== '') {
            $schoolsQuery->where(function ($query) use ($q) {
                $query->where('name_en', 'like', "%{$q}%")
                    ->orWhere('name_ar', 'like', "%{$q}%")
                    ->orWhereHas('schoolUser', function ($uq) use ($q) {
                        $uq->where('username', 'like', "%{$q}%");
                    });
            });
        }

        $schools = $schoolsQuery
            ->paginate(15)
            ->appends(['q' => $q]);

        // AJAX response for live search
        if ($request->ajax()) {
            $rowsHtml = view('admin.schools.partials.rows', compact('schools'))->render();
            $paginationHtml = $schools->links()->render();

            return response()->json([
                'rows' => $rowsHtml,
                'pagination' => $paginationHtml,
                'total' => method_exists($schools, 'total') ? $schools->total() : null,
            ]);
        }

        return view('admin.schools.index', compact('schools', 'q'));
    }

    /**
     * Show the form for creating a new school.
     */
    public function create()
    {
        return view('admin.schools.create');
    }

    /**
     * Store a newly created school and its school account user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:8|confirmed',
            'full_name' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Create school
            $school = School::create([
                'name_en' => $request->name_en,
                'name_ar' => $request->name_ar,
            ]);

            // Create school account user
            User::create([
                'school_id' => $school->id,
                'role' => 'school',
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'full_name' => $request->full_name,
            ]);

            DB::commit();

            return redirect()->route('admin.schools.index')
                ->with('success', 'School and account created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->withErrors(['error' => 'Failed to create school: ' . $e->getMessage()]);
        }
    }

    public function edit(School $school)
    {
        $user = $school->schoolUser()->first();
        return view('admin.schools.edit', compact('school', 'user'));
    }

    public function update(Request $request, School $school)
    {
        $request->validate([
            'name_en' => 'required|string|min:2|max:255',
            'name_ar' => 'required|string|min:2|max:255',
            'username' => 'required|string|min:2|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:8|confirmed',
            'full_name' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Update school
            $school->update([
                'name_en' => $request->name_en,
                'name_ar' => $request->name_ar,
            ]);

            // Update school account user
            $user = $school->schoolUser()->first();
            if ($user) {
                $userData = [
                    'username' => $request->username,
                    'email' => $request->email,
                    'full_name' => $request->full_name,
                ];

                // Update password if provided
                if ($request->filled('password')) {
                    $userData['password'] = Hash::make($request->password);
                }

                $user->update($userData);
            }

            DB::commit();

            return redirect()->route('admin.schools.index')
                ->with('success', 'School updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->withErrors(['error' => 'Failed to update school: ' . $e->getMessage()]);
        }
    }

    public function show(School $school)
    {
        $user = $school->schoolUser()->first();
        return view('admin.schools.show', compact('school', 'user'));
    }
    public function destroy(School $school)
    {
        $school->delete();
        return redirect()->route('admin.schools.index')
            ->with('success', 'School deleted successfully.');
    }
}
