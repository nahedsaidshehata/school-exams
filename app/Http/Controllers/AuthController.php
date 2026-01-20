<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectToDashboard();
        }
        
        return view('auth.login');
    }

    /**
     * Handle login request.
     * Supports login via username OR email + password.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = $request->input('login');
        $password   = $request->input('password');

        $fieldType = filter_var($loginField, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // ✅ Try Admin first (system-level: school_id NULL)
        if (Auth::attempt([$fieldType => $loginField, 'password' => $password, 'role' => 'admin', 'school_id' => null])) {
            $request->session()->regenerate();
            return $this->redirectToDashboard();
        }

        // ✅ Try School account (role=school, school_id NOT NULL)
        if (Auth::attempt([$fieldType => $loginField, 'password' => $password, 'role' => 'school'])) {
            $request->session()->regenerate();
            if (!Auth::user()->school_id) {
                Auth::logout();
                throw ValidationException::withMessages(['login' => ['Invalid school account (no school).']]);
            }
            return $this->redirectToDashboard();
        }

        // ✅ Try Student (role=student) — IMPORTANT: username must be globally unique OR use email
        if (Auth::attempt([$fieldType => $loginField, 'password' => $password, 'role' => 'student'])) {
            $request->session()->regenerate();
            if (!Auth::user()->school_id) {
                Auth::logout();
                throw ValidationException::withMessages(['login' => ['Invalid student account (no school).']]);
            }
            return $this->redirectToDashboard();
        }

        throw ValidationException::withMessages([
            'login' => ['The provided credentials do not match our records.'],
        ]);
    }


    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }

    /**
     * Get authenticated user information.
     */
    public function me()
    {
        $user = Auth::user();
        
        return response()->json([
            'id' => $user->id,
            'role' => $user->role,
            'school_id' => $user->school_id,
            'username' => $user->username,
            'email' => $user->email,
            'full_name' => $user->full_name,
        ]);
    }

    /**
     * Redirect to appropriate dashboard based on role.
     */
    private function redirectToDashboard()
    {
        $user = Auth::user();
        
        return match($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'school' => redirect()->route('school.dashboard'),
            'student' => redirect()->route('student.dashboard'),
            default => redirect()->route('login'),
        };
    }
}
