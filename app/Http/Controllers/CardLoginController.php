<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CardLoginController extends Controller
{
    public function __invoke(string $token)
    {
        $token = trim($token);
        if ($token === '') {
            abort(404);
        }

        $hash = hash('sha256', $token);

        $row = DB::table('student_card_tokens')
            ->where('active', 1)
            ->where('token_hash', $hash)
            ->first();

        if (!$row) {
            abort(404, 'Invalid or expired card.');
        }

        $user = User::where('id', $row->user_id)
            ->where('role', 'student')
            ->first();

        if (!$user) {
            abort(404, 'Student not found.');
        }

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route('student.dashboard');
    }
}
